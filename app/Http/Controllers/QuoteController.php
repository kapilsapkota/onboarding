<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Quote;
use App\Models\QuoteItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Browsershot\Browsershot;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $query = Quote::withCount('items')
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('quote_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $quotes = $query->paginate(15)->withQueryString();

        $statusCounts = Quote::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.quotes.index', compact('quotes', 'statusCounts'));
    }

    // -----------------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------------

    public function create(): View
    {
        $categories = Category::active()
            ->with(['activeProducts'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.quotes.create', compact('categories'));
    }

    // -----------------------------------------------------------------------
    // Store
    // -----------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name'           => 'required|string|max:255',
            'contact_name'          => 'nullable|string|max:255',
            'email'                 => 'required|email|max:255',
            'mobile' => 'required|string|digits:10|starts_with:04,05',

            'website'               => 'nullable|string|max:255',
            'logo'                  => 'nullable|image|max:2048',
            'sharepoint_file_url'   => 'nullable|string|max:500',
            'sharepoint_source_url' => 'nullable|string|max:500',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|string',
            'expires_at'            => 'nullable|date|after:+1 week',
        ]);

        $itemsPayload = json_decode($validated['items'], true);

        if (! is_array($itemsPayload) || empty($itemsPayload)) {
            return back()->withErrors(['items' => 'At least one line item is required.'])->withInput();
        }
        $logoPath = null;

        if ($request->hasFile('logo-input')) {
            $logoPath = $request->file('logo-input')->store('quote-logos', 'public');
        }

        DB::transaction(function () use ($request,$validated, $itemsPayload, $logoPath) {
            $quote = Quote::create([
                'client_name'           => $validated['client_name'],
                'contact_name'          => $validated['contact_name'] ?? null,
                'email'                 => $validated['email'] ?? null,
                'mobile'                => $validated['mobile'] ?? null,
                'website'               => $validated['website'] ?? null,
                'logo_url'             => $logoPath,
                'sharepoint_file_url'   => $validated['sharepoint_file_url'] ?? null,
                'sharepoint_source_url' => $validated['sharepoint_source_url'] ?? null,
                'notes'                 => $validated['notes'] ?? null,
                'status'                => 'draft',
                'expires_at'            => $validated['expires_at'] ?? null,
            ]);

            foreach ($itemsPayload as $index => $item) {
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $setupFee = (float) ($item['setup_fee'] ?? 0);

                $quote->items()->create([
                    'product_id'        => $item['product_id'] ?? null,
                    'quantity'       => $item['quantity'] ?? 1,   // ← include this
                    'category_name'     => $item['category_name'] ?? '',
                    'product_name'      => $item['product_name'] ?? '',
                    'scope_of_works'    => $item['scope_of_works'] ?? null,
                    'key_scope_keyword' => $item['key_scope_keyword'] ?? null,
                    'unit_price'        => $unitPrice,
                    'setup_fee'        => $setupFee,
                    'hourly_rate'       => $item['hourly_rate'] ?? null,
                    'frequency'         => $item['frequency'] ?? 'once_off',
                    'image_url'         => $item['image_url'] ?? null,
                    'notes'             => $item['notes'] ?? null,
                    'sort_order'        => $index,
                ]);
            }

            $quote->recalculateTotals();

            // Store quote number for redirect
            session(['last_created_quote' => $quote->id]);
        });

        $quoteId = session()->pull('last_created_quote');

        return redirect()->route('admin.quotes.show', $quoteId)
            ->with('success', 'Quote created successfully!');
    }

    // -----------------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------------

    public function show(Quote $quote): View
    {
        $quote->load('items.product.category')->withCount('items');
        $defaultSrc = asset('images/default.png');
        $coverSrc    = asset('images/img.png');
        $closingSrc  = asset('images/media/image67.jpg');
        $partnersSrc = asset('images/partners_.png');
        $threeStepRollOutSrc = asset('images/threestep.jpeg');
        $items = $quote->items->map(function ($item) use ($defaultSrc) {

            if (
                $item->product &&
                $item->product->image_url &&
                file_exists(public_path('storage/' . $item->product->image_url))
            ) {
                $item->product_image_src = asset(
                    'storage/' . $item->product->image_url
                );
            } else {
                $item->product_image_src = $defaultSrc;
            }

            return $item;
        });

        $groupedItems = $items
            ->groupBy(function ($item) {
                return $item->product?->category?->name
                    ?? $item->category_name
                    ?? '';
            })
            ->map(function ($categoryItems, $categoryName) {

                $category = $categoryItems->first()?->product?->category;

                return [
                    'name' => $categoryName,

                    'image' => $category?->icon
                        ? asset('storage/' . $category->icon)
                        : null,

                    'items' => $categoryItems,
                ];
            })
            ->values();

        return view('admin.quotes.show', compact(
            'quote',
            'items',
            'groupedItems',
            'defaultSrc',
            'coverSrc','closingSrc','partnersSrc','threeStepRollOutSrc'));
    }

    // -----------------------------------------------------------------------
    // Edit
    // -----------------------------------------------------------------------

    public function edit(Quote $quote): View
    {
        $quote->load('items.product');


        $categories = Category::active()
            ->with(['activeProducts'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.quotes.create', compact('quote', 'categories'));
    }

    // -----------------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------------

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'client_name'           => 'required|string|max:255',
            'contact_name'          => 'nullable|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'mobile'                => 'nullable|string|max:30',
            'website'               => 'nullable|string|max:255',
            'logo_url'              => 'nullable|url|max:500',
            'sharepoint_file_url'   => 'nullable|string|max:500',
            'sharepoint_source_url' => 'nullable|string|max:500',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|string',
            'expires_at'            => 'nullable|date|after:+1 week',
        ]);

        $itemsPayload = json_decode($validated['items'], true);

        $logoPath = null;

        if ($request->hasFile('logo-input')) {
            $logoPath = $request->file('logo-input')->store('quote-logos', 'public');
        }

        if (! is_array($itemsPayload) || empty($itemsPayload)) {
            return back()->withErrors(['items' => 'At least one line item is required.'])->withInput();
        }

        DB::transaction(function () use ($quote, $validated, $itemsPayload, $logoPath) {
            $quote->update([
                'client_name'           => $validated['client_name'],
                'contact_name'          => $validated['contact_name'] ?? null,
                'email'                 => $validated['email'] ?? null,
                'mobile'                => $validated['mobile'] ?? null,
                'website'               => $validated['website'] ?? null,
                'logo_url'              => $logoPath ?? $quote->logo_url,
                'sharepoint_file_url'   => $validated['sharepoint_file_url'] ?? null,
                'sharepoint_source_url' => $validated['sharepoint_source_url'] ?? null,
                'notes'                 => $validated['notes'] ?? null,
                'expires_at'            => $validated['expires_at'] ?? null,
            ]);

            // Replace all items
            $quote->items()->delete();

            foreach ($itemsPayload as $index => $item) {
                $quote->items()->create([
                    'product_id'        => $item['product_id'] ?? null,
                    'quantity'       => $item['quantity'] ?? 1,   // ← include this
                    'category_name'     => $item['category_name'] ?? '',
                    'product_name'      => $item['product_name'] ?? '',
                    'scope_of_works'    => $item['scope_of_works'] ?? null,
                    'key_scope_keyword' => $item['key_scope_keyword'] ?? null,
                    'unit_price'        => (float) ($item['unit_price'] ?? 0),
                    'hourly_rate'       => $item['hourly_rate'] ?? null,
                    'frequency'         => $item['frequency'] ?? 'once_off',
                    'image_url'         => $item['image_url'] ?? null,
                    'notes'             => $item['notes'] ?? null,
                    'sort_order'        => $index,
                ]);
            }

            $quote->recalculateTotals();
        });

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', 'Quote updated successfully!');
    }

    // -----------------------------------------------------------------------
    // Destroy
    // -----------------------------------------------------------------------

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote deleted.');
    }

    // -----------------------------------------------------------------------
    // Send Quote (mark as sent + trigger email/SMS)
    // -----------------------------------------------------------------------

    public function send(Request $request, Quote $quote)
    {
        $request->validate([
            'send_via' => 'required|in:email,sms,both',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'subject' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        if (in_array($request->send_via, ['email', 'both'])) {

            // Generate PDF

            // Mail::to($request->email)->send(...)
        }

        if (in_array($request->send_via, ['sms', 'both'])) {

            // Twilio / MessageBird / Vonage

        }

        $quote->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Quote sent successfully.');
    }

    public function updateStatus(Request $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected',
        ]);

        $updates = ['status' => $validated['status']];

        match ($validated['status']) {
            'sent'     => $updates['sent_at']     = now(),
            'accepted' => $updates['accepted_at'] = now(),
            'rejected' => $updates['rejected_at'] = now(),
            default    => null,
        };

        $quote->update($updates);

        return back()->with('success', 'Status updated.');
    }

    public function duplicate(Quote $quote): RedirectResponse
    {
        $newQuote = $quote->replicate(['quote_number', 'status', 'sent_at', 'accepted_at', 'rejected_at']);
        $newQuote->status       = 'draft';
        $newQuote->quote_number = Quote::generateQuoteNumber();
        $newQuote->save();

        foreach ($quote->items as $item) {
            $newItem = $item->replicate(['quote_id']);
            $newItem->quote_id = $newQuote->id;
            $newItem->save();
        }

        $newQuote->recalculateTotals();

        return redirect()->route('admin.quotes.edit', $newQuote)
            ->with('success', 'Quote duplicated — review and save changes.');
    }

//    public function pdf(Request $request, Quote $quote)
//    {
//        ini_set('memory_limit', '512M');
//        set_time_limit(120);
//
//        $pageW = 1122;
//        $pageH = 794;
//
//        $halfW = 561;
//
//
//        $coverSrc = self::cachedBase64('images/img.png', $pageW, $pageH);
//
//        $defaultSrc = self::cachedBase64('images/default.png', $halfW, $pageH);
//
//        $closingSrc = self::cachedBase64('images/media/image67.jpg', $pageW, $pageH);
//
//        $partnersSrc = self::cachedBase64('images/partners_.png', $pageW, $pageH);
//
//        $configImages = collect(config('quote.images') ?? [])
//            ->map(fn ($img) => [
//                'placeholder' => $img['placeholder'],
//                'src' => self::cachedBase64(
//                    $img['image'],
//                    $pageW,
//                    $pageH
//                ),
//            ])
//            ->filter(fn ($img) => $img['src'] !== null)
//            ->values();
//
//        $clientLogoSrc = null;
//        if ($quote->logo_url) {
//            $clientLogoSrc = self::cropToBase64(
//                public_path('storage/' . $quote->logo_url), 360, 200, false  // fit, don't crop
//            );
//        }
//        $quote->load('items.product:id,name,image_url,description');
//        $items = $quote->items->each(function ($item) use ($halfW, $pageH) {
//            $item->product_image_src = $item->product?->image_url
//                ? self::cachedBase64(
//                    'storage/' . $item->product->image_url,
//                    $halfW,
//                    $pageH
//                )
//                : null;
//        });
//
//        // Partner logos — fit inside cell, no crop
////        $partners = \App\Models\Company::all()->map(function ($partner) {
////            $path = public_path('images/' . $partner['logo']);
////            return [
////                'name' => $partner['name'],
////                'src'  => file_exists($path)
////                    ? self::cropToBase64($path, 280, 100, false)
////                    : null,
////            ];
////        });
//
//        $data = [
//            'quote'              => $quote,
//            'items'              => $items,
////            'partners'           => $partners,
//            'partnersSrc'        => $partnersSrc,
//            'configImages'       => $configImages,
//            'coverSrc'           => $coverSrc,
//            'defaultSrc'         => $defaultSrc,
//            'closingSrc'         => $closingSrc,
//            'clientLogoSrc'      => $clientLogoSrc,
//            'stageColumns'       => collect(config('quote.stage_columns')),
//            'stageAccents'       => ['#fbbf24', '#f97316', '#c2410c'],
//            'termsAndConditions' => $quote->terms_and_conditions
//                ?: config('quote.default_terms'),
//        ];
//
//        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.quotes.pdf', $data)
//            ->setPaper('a4', 'landscape')
//            ->setOption('isRemoteEnabled', true)
//            ->setOption('isFontSubsettingEnabled', true)
//            ->setOption('defaultMediaType', 'print')
//            ->setOption('dpi', 96);
//
//        return $pdf->stream($quote->quote_number . '.pdf',['Attachment' => false]);
//    }


    public function pdf(Request $request, Quote $quote)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $quote->load([
            'items.product.category'
        ]);

        // Static images
        $coverSrc    = public_path('images/img.png');
        $defaultSrc  = public_path('images/default.png');
        $closingSrc  = public_path('images/media/image67.jpg');
        $partnersSrc = public_path('images/partners_.png');
        $threeStepRollOutSrc = public_path('images/threestep.jpeg');

        $configImages = collect(config('quote.images', []))
            ->map(function ($img) {
                return [
                    'placeholder' => $img['placeholder'],
                    'src' => public_path($img['image']),
                ];
            })
            ->filter(fn ($img) => file_exists($img['src']))
            ->values();

        // Client logo
        $clientLogoSrc = null;

        if (
            $quote->logo_url &&
            file_exists(public_path('storage/' . $quote->logo_url))
        ) {
            $clientLogoSrc = public_path('storage/' . $quote->logo_url);
        }

        // Product images
        $items = $quote->items->map(function ($item) use ($defaultSrc) {

            if (
                $item->product &&
                $item->product->image_url &&
                file_exists(public_path('storage/' . $item->product->image_url))
            ) {
                $item->product_image_src = public_path(
                    'storage/' . $item->product->image_url
                );
            } else {
                $item->product_image_src = $defaultSrc;
            }

            return $item;
        });

        $groupedItems = $items
            ->groupBy(function ($item) {
                return $item->product?->category?->name
                    ?? $item->category_name
                    ?? '';
            })
            ->map(function ($categoryItems, $categoryName) {

                $category = $categoryItems->first()?->product?->category;

                return [
                    'name' => $categoryName,

                    'image' => $category?->icon
                        ? public_path('storage/' . $category->icon)
                        : null,

                    'items' => $categoryItems,
                ];
            })
            ->values();

        $data = [
            'quote' => $quote,
            'items' => $items,
            'groupedItems' => $groupedItems,
            'coverSrc' => $coverSrc,
            'defaultSrc' => $defaultSrc,
            'closingSrc' => $closingSrc,
            'partnersSrc' => $partnersSrc,
            'threeStepRollOutSrc' => $threeStepRollOutSrc,
            'clientLogoSrc' => $clientLogoSrc,
            'configImages' => $configImages,
            'stageColumns' => collect(config('quote.stage_columns')),
            'stageAccents' => [
                '#fbbf24',
                '#f97316',
                '#c2410c',
            ],

            'termsAndConditions' =>
                $quote->terms_and_conditions
                    ?: config('quote.default_terms'),
        ];
        $pdf = Pdf::loadView('admin.quotes.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', false)
            ->setOption('defaultMediaType', 'print')
            ->setOption('dpi', 96)
            ->setWarnings(false);

        return $pdf->stream(
            "{$quote->quote_number}.pdf",
            [
                'Attachment' => false,
            ]
        );
    }

    private static function cachedBase64(string $path, int $width, int $height): ?string
    {
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            return null;
        }

        $key = 'quote_image:' . md5($fullPath . $width . $height . filemtime($fullPath));

        return Cache::rememberForever($key, function () use ($fullPath, $width, $height) {
            return self::cropToBase64($fullPath, $width, $height);
        });
    }

    /**
     * Load an image, optionally crop it to fill $targetW × $targetH
     * (centre-crop, like object-fit: cover), then return a base64 data URI.
     *
     * Pass $crop = false for logos / portraits where you want the whole
     * image to fit inside the box (like object-fit: contain).
     *
     * Uses only GD — no extra packages needed.
     */
    private static function cropToBase64(
        string $path,
        int    $targetW,
        int    $targetH,
        bool   $crop = true   // true = cover crop, false = contain fit
    ): ?string {
        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        [$origW, $origH, $type] = $info;

        $mimeMap = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_GIF  => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
        ];

        if (!isset($mimeMap[$type])) {
            return null;
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => false,
        };

        if (!$src) {
            return null;
        }

        if ($crop) {
            // ── COVER CROP ───────────────────────────────────────────
            // Scale so the image fills the target box on both axes,
            // then crop any overhang from the centre.
            $scale = max($targetW / $origW, $targetH / $origH);

            $scaledW = (int) round($origW * $scale);
            $scaledH = (int) round($origH * $scale);

            // Centre-crop offsets
            $srcX = (int) round(($scaledW - $targetW) / 2 / $scale);
            $srcY = (int) round(($scaledH - $targetH) / 2 / $scale);
            $srcW = (int) round($targetW / $scale);
            $srcH = (int) round($targetH / $scale);

            $dst = imagecreatetruecolor($targetW, $targetH);
            self::preserveTransparency($dst, $type);
            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);

        } else {
            // ── CONTAIN FIT ──────────────────────────────────────────
            // Scale to fit inside the box without cropping.
            // Canvas is exactly $targetW × $targetH (white background).
            $scale = min($targetW / $origW, $targetH / $origH, 1.0);
            $fitW  = (int) round($origW * $scale);
            $fitH  = (int) round($origH * $scale);

            $dst = imagecreatetruecolor($targetW, $targetH);

            // White background for contain mode
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);

            // Centre the fitted image on the canvas
            $offsetX = (int) round(($targetW - $fitW) / 2);
            $offsetY = (int) round(($targetH - $fitH) / 2);

            imagecopyresampled($dst, $src, $offsetX, $offsetY, 0, 0, $fitW, $fitH, $origW, $origH);
        }

        imagedestroy($src);

        ob_start();
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($dst, null, 85),
            IMAGETYPE_PNG  => imagepng($dst, null, 6),
            IMAGETYPE_GIF  => imagegif($dst),
            IMAGETYPE_WEBP => imagewebp($dst, null, 85),
            default        => imagejpeg($dst, null, 85),
        };
        $raw = ob_get_clean();
        imagedestroy($dst);

        return 'data:' . $mimeMap[$type] . ';base64,' . base64_encode($raw);
    }

    /** Enable alpha/transparency on a GD canvas for PNG/GIF output. */
    private static function preserveTransparency($dst, int $type): void
    {
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, imagesx($dst), imagesy($dst), $transparent);
        }
    }

//    public function pdf(Request $request, Quote $quote)
//    {
//        // 1. Extend limits explicitly. Renders occur via Chrome,
//        // but the PHP thread still waits for the browser binary to finish.
//        ini_set('memory_limit', '256M');
//        set_time_limit(120);
//
//        $data = [
//            'quote'              => $quote->load('items'),
//            'partners'           => \App\Models\Company::all(),
//            'stageColumns'       => collect(config('quote.stage_columns')),
//            'stageAccents'       => ['#fbbf24', '#f97316', '#c2410c'],
//            'termsAndConditions' => $quote->terms_and_conditions ?: config('quote.default_terms'),
//        ];
//
//        // 2. Render the raw Blade HTML string
//        $html = view('admin.quotes.pdf', $data)->render();
//
//        $fileName = 'quote-' . $quote->id . '.pdf';
//
//        // Use Laravel's standard temporary storage disk location
//        $path = storage_path('app/private/' . $fileName);
//
//        // Ensure the directory exists before saving
//        if (!file_exists(dirname($path))) {
//            mkdir(dirname($path), 0755, true);
//        }
//
//        // 3. Execute Browsershot Pipeline
//        Browsershot::html($html)
//            ->format('A4')
//            ->landscape()
//            ->showBackground()
//            // CRITICAL: Tells Chrome to ignore untrusted local SSL certs on XAMPP
//            ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--ignore-certificate-errors'])
//            // For heavy 34-page documents, give Chromium up to 90 seconds to render
//            ->timeout(90)
//            // Ensures heavy images are fully decoded and rendered in the DOM
//            ->waitUntilNetworkIdle()
//            ->save($path);
//
//        // 4. Safely return the stream and purge the file from disk afterward
//        return response()->download($path, $fileName)->deleteFileAfterSend(true);
//    }

    public function showSignForm(Request $request, Quote $quote)
    {
        // 1. Check if the URL has been tampered with or has expired
        if (! $request->hasValidSignature()) {
            return view('admin.quotes.message', [
                'title' => 'Link Expired',
                'message' => 'This secure link is invalid or has expired. Please request a new link from our team.'
            ]);
        }

        // 2. Check if the quote has ALREADY been signed (Optional but highly recommended)
        // Assuming your Quote model has a 'signatures' relationship or an 'accepted_at' column
        if ($quote->signatures()->exists()) {
            return view('admin.quotes.message', [
                'title' => 'Already Signed',
                'message' => 'This quote has already been signed and accepted.'
            ]);
        }

        // 3. Check business-logic expiry (e.g., if the quote itself has a valid_until date in the DB)
        if ($quote->expires_at && $quote->expires_at->isPast()) {
            return view('admin.quotes.message', [
                'title' => 'Quote Expired',
                'message' => 'This quote expired on ' . $quote->expires_at->format('d/m/Y') . '. Please contact us for an updated quote.'
            ]);
        }

        // If all security checks pass, show the form
        return view('admin.quotes.sign-form', compact('quote'));
    }
    /**
     * Process form submission data, save signature file, and store metadata.
     */
    public function saveSignature(Request $request, Quote $quote)
    {
        // 1. Validate the incoming request
        $request->validate([
            'company_name'      => 'nullable|string|max:255',
            'authorised_person' => 'required|string|max:255',
            'position'          => 'nullable|string|max:255',
            'signature_data'    => 'required|string', // Base64 string
        ]);

        // 2. Process and save the Base64 image to a folder
        $base64Image = $request->signature_data;

        // Strip out the "data:image/png;base64," part
        @list($type, $fileData) = explode(';', $base64Image);
        @list(, $fileData)      = explode(',', $fileData);

        // Decode the image
        $decodedImage = base64_decode($fileData);

        // Create a unique filename
        $fileName = 'quote_' . $quote->id . '_sign_' . Str::random(10) . '.png';
        $filePath = 'signatures/' . $fileName;

        // Save to the 'local' disk (storage/app/signatures) to keep it private,
        // or 'public' (storage/app/public/signatures) if you prefer.
        Storage::disk('local')->put($filePath, $decodedImage);

        // 3. Save the signature record with metadata to the new table
        $quote->signatures()->create([
            'company_name'      => $request->company_name,
            'authorised_person' => $request->authorised_person,
            'position'          => $request->position,
            'signature_path'    => $filePath,
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
            'signed_at'         => now(),
        ]);

        // Optional: Mark the quote itself as accepted/signed
        if (is_null($quote->accepted_at)) {
            $quote->update(['accepted_at' => now()]);
        }

        // 4. Return JSON response for the AJAX fetch request
        return response()->json([
            'success' => true,
            'message' => 'Signature captured and saved successfully.'
        ]);
    }
}

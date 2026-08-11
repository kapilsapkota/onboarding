<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\Quote\QuoteRequest;
use App\Http\Requests\Admin\Quote\SendQuoteRequest;
use App\Models\Category;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteSignature;
use App\Services\Quotes\QuoteDeliveryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteDeliveryService $deliveryService,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $query = Quote::query()
            ->with('items')
            ->withCount('items')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('quote_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $quotes = $query->paginate(25)->withQueryString();

        $statusCounts = Quote::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.quotes.index', compact('quotes', 'statusCounts'));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(): View
    {
        $categories = Category::active()
            ->with(['activeProducts'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.quotes.create', compact('categories'));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(QuoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $itemsPayload = json_decode($validated['items'], true);

        if (! is_array($itemsPayload) || empty($itemsPayload)) {
            return back()
                ->withErrors(['items' => 'At least one line item is required.'])
                ->withInput();
        }

        $logoPath = null;

        try {
            if ($request->hasFile('logo-input')) {
                $logoPath = $request->file('logo-input')
                    ->store('quote-logos', 'public');
            }

            $quote = DB::transaction(function () use ($validated, $itemsPayload, $logoPath) {
                $quote = Quote::create([
                    'client_name'           => $validated['client_name'],
                    'contact_name'          => $validated['contact_name'] ?? null,
                    'email'                 => $validated['email'] ?? null,
                    'mobile'                => $validated['mobile'] ?? null,
                    'website'               => $validated['website'] ?? null,
                    'logo_url'              => $logoPath,
                    'sharepoint_file_url'   => $validated['sharepoint_file_url'] ?? null,
                    'sharepoint_source_url' => $validated['sharepoint_source_url'] ?? null,
                    'notes'                 => $validated['notes'] ?? null,
                    'status'                => 'draft',
                    'expires_at'            => $validated['expires_at'] ?? null,
                ]);

                foreach ($itemsPayload as $index => $item) {
                    $quote->items()->create([
                        'product_id'        => $item['product_id'] ?? null,
                        'quantity'          => $item['quantity'] ?? 1,
                        'category_name'     => $item['category_name'] ?? '',
                        'product_name'      => $item['product_name'] ?? '',
                        'scope_of_works'    => $item['scope_of_works'] ?? null,
                        'key_scope_keyword' => $item['key_scope_keyword'] ?? null,
                        'unit_price'        => (float) ($item['unit_price'] ?? 0),
                        'setup_fee'         => (float) ($item['setup_fee'] ?? 0),
                        'hourly_rate'       => $item['hourly_rate'] ?? null,
                        'frequency'         => $item['frequency'] ?? 'once_off',
                        'image_url'         => $item['image_url'] ?? null,
                        'notes'             => $item['notes'] ?? null,
                        'sort_order'        => $index,
                    ]);
                }

                $quote->recalculateTotals();

                return $quote;
            });

            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with('success', 'Quote created successfully!');

        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['error' => 'Unable to create the quote. Please try again.'])
                ->withInput();
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Quote $quote): View
    {
        $data = $this->buildQuoteData($quote, forPdf: false);

        // Load delivery history for the status panel.
        $quote->load([
            'latestDelivery.attempts',
            'deliveries' => fn ($q) => $q->with('attempts')->latest()->limit(5),
        ]);

        return view('admin.quotes.show', $data);
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(Quote $quote): View
    {
        $quote->load('items.product');

        $categories = Category::active()
            ->with(['activeProducts'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.quotes.create', compact('quote', 'categories'));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        if (! $quote->canEdit()) {
            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with(
                    'error',
                    "Quote {$quote->quote_number} cannot be edited because it is already {$quote->status}."
                );
        }
        $validated    = $request->validated();
        $itemsPayload = json_decode($validated['items'], true);

        if (! is_array($itemsPayload) || empty($itemsPayload)) {
            return back()
                ->withErrors(['items' => 'At least one line item is required.'])
                ->withInput();
        }

        $logoPath = null;

        try {
            if ($request->hasFile('logo-input')) {
                $logoPath = $request->file('logo-input')
                    ->store('quote-logos', 'public');
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

                $quote->items()->delete();

                foreach ($itemsPayload as $index => $item) {
                    $quote->items()->create([
                        'product_id'        => $item['product_id'] ?? null,
                        'quantity'          => $item['quantity'] ?? 1,
                        'category_name'     => $item['category_name'] ?? '',
                        'product_name'      => $item['product_name'] ?? '',
                        'scope_of_works'    => $item['scope_of_works'] ?? null,
                        'key_scope_keyword' => $item['key_scope_keyword'] ?? null,
                        'unit_price'        => (float) ($item['unit_price'] ?? 0),
                        'setup_fee'         => (float) ($item['setup_fee'] ?? 0),
                        'hourly_rate'       => $item['hourly_rate'] ?? null,
                        'frequency'         => $item['frequency'] ?? 'once_off',
                        'image_url'         => $item['image_url'] ?? null,
                        'notes'             => $item['notes'] ?? null,
                        'sort_order'        => $index,
                    ]);
                }

                $quote->recalculateTotals();
            });

            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with('success', 'Quote updated successfully!');

        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['error' => 'Unable to update the quote. Please try again.'])
                ->withInput();
        }
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()
            ->route('admin.quotes.index')
            ->with('success', 'Quote deleted.');
    }

    // -------------------------------------------------------------------------
    // Send
    //
    // Validates input, creates the delivery record, dispatches the async job,
    // and returns immediately. No PDF generation, email, or SMS here.
    // -------------------------------------------------------------------------

    public function send(SendQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validated();

        // Validate that at least one viable channel exists on the quote.
        // The form can request email/SMS but the quote may have no address.
        $wantsEmail = (bool) ($validated['send_email'] ?? false);
        $wantsSms   = (bool) ($validated['send_sms'] ?? false);

        if ($wantsEmail && empty($quote->email)) {
            return back()->withErrors([
                'send_email' => 'This quote has no email address. Add one to the quote before sending via email.',
            ]);
        }

        if ($wantsSms && empty($quote->mobile)) {
            return back()->withErrors([
                'send_sms' => 'This quote has no mobile number. Add one to the quote before sending via SMS.',
            ]);
        }

        try {
            ['delivery' => $delivery, 'already_pending' => $alreadyPending] =
                $this->deliveryService->createAndDispatch(
                    quote:     $quote,
                    validated: [
                        'send_email'    => $wantsEmail,
                        'send_sms'      => $wantsSms,
                        'email_message' => $validated['extra_message'] ?? null,
                        'sms_message'   => $validated['extra_sms_message'] ?? null,
                    ],
                    userId: $request->user()->id,
                );

            if ($alreadyPending) {
                return redirect()
                    ->route('admin.quotes.show', $quote)
                    ->with('info', 'This quote already has a delivery in progress. Please wait for it to complete.');
            }

            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with('success', 'Quote delivery started. You will see the result below shortly.');

        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'error' => 'Unable to start the quote delivery. Please try again.',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Update status
    // -------------------------------------------------------------------------

    public function updateStatus(Request $request, Quote $quote)
    {
        $request->validate([
            'status' => [
                'required',
                Rule::in(['draft', 'sent', 'accepted', 'rejected']),
            ],
        ]);

        $newStatus = $request->string('status')->toString();

        if (! $quote->canTransitionTo($newStatus)) {
            return back()->with(
                'error',
                "A {$quote->status} quote cannot be changed to {$newStatus}."
            );
        }

        $updates = [
            'status' => $newStatus,
        ];

        if ($newStatus === 'sent' && is_null($quote->sent_at)) {
            $updates['sent_at'] = now();
        }

        if ($newStatus === 'accepted' && is_null($quote->accepted_at)) {
            $updates['accepted_at'] = now();
        }

        if ($newStatus === 'rejected' && is_null($quote->rejected_at)) {
            $updates['rejected_at'] = now();
        }

        $quote->update($updates);

        return back()->with(
            'success',
            "Quote {$quote->quote_number} status updated to " .
            ucfirst($newStatus) . '.'
        );
    }

    // -------------------------------------------------------------------------
    // Duplicate
    // -------------------------------------------------------------------------

    public function duplicate(Quote $quote): RedirectResponse
    {
        $newQuote = $quote->replicate([
            'quote_number',
            'status',
            'sent_at',
            'accepted_at',
            'rejected_at',
        ]);

        $newQuote->status       = 'draft';
        $newQuote->quote_number = Quote::generateQuoteNumber();
        $newQuote->save();

        foreach ($quote->items as $item) {
            $newItem           = $item->replicate(['quote_id']);
            $newItem->quote_id = $newQuote->id;
            $newItem->save();
        }

        $newQuote->recalculateTotals();

        return redirect()
            ->route('admin.quotes.edit', $newQuote)
            ->with('success', 'Quote duplicated — review and save changes.');
    }

    // -------------------------------------------------------------------------
    // PDF stream
    // -------------------------------------------------------------------------

    public function pdf(Request $request, Quote $quote): mixed
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(60);

        $data = $this->buildQuoteData($quote, forPdf: true);

        $pdf = Pdf::loadView('admin.quotes.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('dpi', 96)
            ->setWarnings(false);

        return $pdf->stream($quote->getPdfFilenameAttribute());
    }

    // -------------------------------------------------------------------------
    // Data builder (shared by show() and pdf())
    // -------------------------------------------------------------------------

    private function buildQuoteData(Quote $quote, bool $forPdf = false): array
    {
        $quote->load(['items.product.category','signatures']);

        $path = fn (string $storagePath) => $forPdf
            ? public_path('storage/' . $storagePath)
            : asset('storage/' . $storagePath);

        $staticPath = fn (string $publicPath) => $forPdf
            ? public_path($publicPath)
            : asset($publicPath);

        $exists = fn (string $storagePath) => file_exists(
            public_path('storage/' . $storagePath)
        );

        $signature = $quote->signatures
            ->sortByDesc('signed_at')
            ->first();

        $signatureSrc = null;

        if ($signature?->signature_path) {
            $signaturePath = Storage::disk('private')
                ->path($signature->signature_path);

            if (file_exists($signaturePath)) {
                $signatureSrc = $forPdf
                    ? $signaturePath
                    : route('quotes.signature', [
                        'quote' => $quote->id,
                        'signature' => $signature->id,
                    ]);
            }
        }


        $coverSrc            = $staticPath('images/img.png');
        $defaultSrc          = $staticPath('images/default.png');
        $closingSrc          = $staticPath('images/media/image67.jpg');
        $partnersSrc         = $staticPath('images/partners_.png');
        $threeStepRollOutSrc = $staticPath('images/three-step.jpeg');

        $configImages = collect(config('quote.images', []))
            ->map(function ($img) use ($staticPath, $forPdf) {
                $src = $staticPath($img['image']);

                if ($forPdf && ! file_exists($src)) {
                    return null;
                }

                return [
                    'placeholder' => $img['placeholder'],
                    'src'         => $src,
                ];
            })
            ->filter()
            ->values();

        $clientLogoSrc = null;
        if ($quote->logo_url && $exists($quote->logo_url)) {
            $clientLogoSrc = $path($quote->logo_url);
        }

        $items = $quote->items->map(function ($item) use ($path, $defaultSrc, $exists) {
            $item->product_image_src = ($item->product?->image_url && $exists($item->product->image_url))
                ? $path($item->product->image_url)
                : $defaultSrc;

            return $item;
        });

        $groupedItems = $items
            ->groupBy(fn ($item) => $item->product?->category?->name ?? $item->category_name ?? '')
            ->map(function ($categoryItems, $categoryName) use ($path) {
                $category = $categoryItems->first()?->product?->category;

                return [
                    'name'       => $categoryName,
                    'sort_order' => $category?->sort_order ?? PHP_INT_MAX,
                    'image'      => $category?->icon ? $path($category->icon) : null,
                    'items'      => $categoryItems
                        ->sortBy(fn ($item) => $item->product?->sort_order ?? PHP_INT_MAX)
                        ->values(),
                ];
            })
            ->sortBy('sort_order')
            ->values();

        return [
            'quote'               => $quote,
            'items'               => $items,
            'groupedItems'        => $groupedItems,
            'coverSrc'            => $coverSrc,
            'defaultSrc'          => $defaultSrc,
            'closingSrc'          => $closingSrc,
            'partnersSrc'         => $partnersSrc,
            'threeStepRollOutSrc' => $threeStepRollOutSrc,
            'clientLogoSrc'       => $clientLogoSrc,
            'configImages'        => $configImages,
            'stageColumns'        => collect(config('quote.stage_columns')),
            'stageAccents'        => ['#fbbf24', '#f97316', '#c2410c'],
            'termsAndConditions'  => $quote->terms_and_conditions
                ?? config('quote.default_terms'),
            'signature'           => $signature,
            'signatureSrc'        => $signatureSrc,
        ];
    }

    // -------------------------------------------------------------------------
    // Image helpers (unchanged from original)
    // -------------------------------------------------------------------------

    private static function cachedBase64(string $path, int $width, int $height): ?string
    {
        $fullPath = public_path($path);

        if (! file_exists($fullPath)) {
            return null;
        }

        $key = 'quote_image:' . md5($fullPath . $width . $height . filemtime($fullPath));

        return Cache::rememberForever($key, function () use ($fullPath, $width, $height) {
            return self::cropToBase64($fullPath, $width, $height);
        });
    }

    private static function cropToBase64(
        string $path,
        int    $targetW,
        int    $targetH,
        bool   $crop = true
    ): ?string {
        if (! file_exists($path) || ! is_readable($path)) {
            return null;
        }

        $info = @getimagesize($path);

        if (! $info) {
            return null;
        }

        [$origW, $origH, $type] = $info;

        $mimeMap = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_GIF  => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
        ];

        if (! isset($mimeMap[$type])) {
            return null;
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => false,
        };

        if (! $src) {
            return null;
        }

        if ($crop) {
            $scale   = max($targetW / $origW, $targetH / $origH);
            $scaledW = (int) round($origW * $scale);
            $scaledH = (int) round($origH * $scale);
            $srcX    = (int) round(($scaledW - $targetW) / 2 / $scale);
            $srcY    = (int) round(($scaledH - $targetH) / 2 / $scale);
            $srcW    = (int) round($targetW / $scale);
            $srcH    = (int) round($targetH / $scale);
            $dst     = imagecreatetruecolor($targetW, $targetH);
            self::preserveTransparency($dst, $type);
            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);
        } else {
            $scale   = min($targetW / $origW, $targetH / $origH, 1.0);
            $fitW    = (int) round($origW * $scale);
            $fitH    = (int) round($origH * $scale);
            $dst     = imagecreatetruecolor($targetW, $targetH);
            $white   = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);
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

    private static function preserveTransparency(mixed $dst, int $type): void
    {
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, imagesx($dst), imagesy($dst), $transparent);
        }
    }
}

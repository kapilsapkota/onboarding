<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteDelivery;
use App\Models\QuoteSignature;
use App\Services\Quotes\QuotePublicLinkService;
use App\Services\Quotes\QuotePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Customer-facing public quote viewer.
 *
 * No authentication required.
 * Resolved via the opaque 64-character token stored on QuoteDelivery.
 */
class PublicQuoteController extends Controller
{
    public function __construct(
        private readonly QuotePublicLinkService $publicLinkService,
        private readonly QuotePdfService        $pdfService,
    ) {}

    // -------------------------------------------------------------------------
    // Show — customer quote page
    // -------------------------------------------------------------------------

    public function show(Request $request, string $token): View
    {
        $delivery = $this->resolveDelivery($token);

        $quote = $delivery->quote;
        $quote->loadMissing(['items.product.category']);

        $groupedItems = $quote->items
            ->groupBy(fn ($item) => $item->product?->category?->name ?? $item->category_name ?? 'Other')
            ->map(fn ($items, $name) => [
                'name'  => $name,
                'items' => $items->values(),
            ])
            ->values();

        // Whether to show the signature panel.
        $alreadySigned = $quote->signatures()->exists();
        $isExpired     = $quote->expires_at && $quote->expires_at->isPast();

        // The public PDF URL — used by the iframe.
        $pdfUrl = route('quotes.public.pdf', ['token' => $token]);

        // The signature save URL — already public (no auth middleware).
        $signatureUrl = route('quotes.save-signature', ['quote' => $quote->id]);

        return view('quotes.public.show', compact(
            'quote',
            'delivery',
            'groupedItems',
            'alreadySigned',
            'isExpired',
            'pdfUrl',
            'signatureUrl',
            'token',
        ));
    }

    // -------------------------------------------------------------------------
    // PDF — stream stored PDF to the browser
    //
    public function pdf(Request $request, string $token): Response
    {
        $delivery = $this->resolveDelivery($token);

        $quote = $delivery->quote->load(['items.product.category']);

        if ($this->pdfService->pdfExists($delivery)) {
            $content  = $this->pdfService->getContent($delivery);
            $filename = $delivery->pdf_filename ?? $quote->getPdfFilenameAttribute();

            return response($content, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length'      => strlen($content),
                'Cache-Control'       => 'private, max-age=3600',
            ]);
        }

        ini_set('memory_limit', '1024M');
        set_time_limit(120);

        $data = $this->buildPdfData($quote);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.quotes.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('dpi', 96)
            ->setWarnings(false);

        $filename = $quote->getPdfFilenameAttribute();

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a QuoteDelivery from a public token.
     * Aborts with 404 for any unknown or invalid token.
     */
    private function resolveDelivery(string $token): QuoteDelivery
    {
        $delivery = $this->publicLinkService->findDeliveryByToken($token);

        if (! $delivery) {
            abort(404);
        }

        return $delivery;
    }

    /**
     * Build PDF view data using absolute paths (required for DomPDF).
     * Mirrors QuotePdfService::buildPdfData() — used only for the fallback path.
     */
    private function buildPdfData(\App\Models\Quote $quote): array
    {
        $path       = fn (string $p) => public_path('storage/' . $p);
        $staticPath = fn (string $p) => public_path($p);
        $exists     = fn (string $p) => file_exists(public_path('storage/' . $p));

        $defaultSrc = $staticPath('images/default.png');

        $configImages = collect(config('quote.images', []))
            ->map(function ($img) use ($staticPath) {
                $src = $staticPath($img['image']);
                return file_exists($src) ? ['placeholder' => $img['placeholder'], 'src' => $src] : null;
            })
            ->filter()->values();

        $clientLogoSrc = ($quote->logo_url && $exists($quote->logo_url))
            ? $path($quote->logo_url)
            : null;

        $items = $quote->items->map(function ($item) use ($path, $defaultSrc, $exists) {
            $item->product_image_src = ($item->product?->image_url && $exists($item->product->image_url))
                ? $path($item->product->image_url)
                : $defaultSrc;
            return $item;
        });

        $groupedItems = $items
            ->groupBy(fn ($i) => $i->product?->category?->name ?? $i->category_name ?? '')
            ->map(function ($categoryItems, $categoryName) use ($path) {
                $category = $categoryItems->first()?->product?->category;
                return [
                    'name'       => $categoryName,
                    'sort_order' => $category?->sort_order ?? PHP_INT_MAX,
                    'image'      => $category?->icon ? $path($category->icon) : null,
                    'items'      => $categoryItems->sortBy(fn ($i) => $i->product?->sort_order ?? PHP_INT_MAX)->values(),
                ];
            })
            ->sortBy('sort_order')->values();

        return [
            'quote'               => $quote,
            'items'               => $items,
            'groupedItems'        => $groupedItems,
            'coverSrc'            => $staticPath('images/img.png'),
            'defaultSrc'          => $defaultSrc,
            'closingSrc'          => $staticPath('images/media/image67.jpg'),
            'partnersSrc'         => $staticPath('images/partners_.png'),
            'threeStepRollOutSrc' => $staticPath('images/threestep.jpeg'),
            'clientLogoSrc'       => $clientLogoSrc,
            'configImages'        => $configImages,
            'stageColumns'        => collect(config('quote.stage_columns')),
            'stageAccents'        => ['#fbbf24', '#f97316', '#c2410c'],
            'termsAndConditions'  => $quote->terms_and_conditions ?? config('quote.default_terms'),
        ];
    }

    public function signature(Quote $quote, QuoteSignature $signature)
    {
        abort_unless(
            $signature->quote_id === $quote->id,
            404
        );

        abort_unless(
            $signature->signature_path,
            404
        );

        $disk = Storage::disk('private');

        abort_unless(
            $disk->exists($signature->signature_path),
            404
        );

        return response()->file(
            $disk->path($signature->signature_path),
            [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Quote signing
    // -------------------------------------------------------------------------

    public function showSignForm(Request $request, Quote $quote): mixed
    {
        if (! $request->hasValidSignature()) {
            return view('admin.quotes.message', [
                'title'   => 'Link Expired',
                'message' => 'This secure link is invalid or has expired. Please request a new link from our team.',
            ]);
        }

        if ($quote->signatures()->exists()) {
            return view('admin.quotes.message', [
                'title'   => 'Already Signed',
                'message' => 'This quote has already been signed and accepted.',
            ]);
        }

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            return view('admin.quotes.message', [
                'title'   => 'Quote Expired',
                'message' => 'This quote expired on ' . $quote->expires_at->format('d/m/Y') . '. Please contact us for an updated quote.',
            ]);
        }

        return view('admin.quotes.sign-form', compact('quote'));
    }

    public function saveSignature(Request $request, Quote $quote): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'company_name'      => 'nullable|string|max:255',
            'authorised_person' => 'required|string|max:255',
            'position'          => 'nullable|string|max:255',
            'signature_data'    => 'required|string',
        ]);

        $base64Image = $request->signature_data;

        @list($type, $fileData) = explode(';', $base64Image);
        @list(, $fileData)      = explode(',', $fileData);

        $decodedImage = base64_decode($fileData);
        $fileName     = 'quote_' . $quote->id . '_sign_' . Str::random(10) . '.png';
        $filePath     = 'signatures/' . $fileName;

        Storage::disk('local')->put($filePath, $decodedImage);

        $quote->signatures()->create([
            'company_name'      => $request->company_name,
            'authorised_person' => $request->authorised_person,
            'position'          => $request->position,
            'signature_path'    => $filePath,
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
            'signed_at'         => now(),
        ]);

        if (is_null($quote->accepted_at)) {
            $quote->update([
                'accepted_at' => now(),
                'status'      => 'accepted'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your signature has been recorded and the proposal has been accepted.',
        ]);
    }

}

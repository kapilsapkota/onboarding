<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\QuoteDelivery;
use App\Models\QuoteDeliveryAttempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generates the quote PDF and stores it on disk.
 *
 * Reuse priority (checked in order):
 *
 *   1. This delivery already has a PDF on disk
 *      → return it immediately, no generation needed.
 *
 *   2. A previous delivery for the same quote has a PDF on disk,
 *      AND that PDF was generated after the quote was last modified
 *      → copy it to this delivery's storage path and record it.
 *      This avoids regenerating an identical PDF on every send.
 *
 *   3. No reusable PDF found → generate fresh, store, record.
 *
 * Why copy rather than share the same path?
 *   Each delivery owns its own PDF path so they are independently
 *   retryable, deletable, and auditable. Sharing a path would mean
 *   deleting one delivery's PDF silently breaks another.
 */
class QuotePdfService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Generate (or reuse) and store the PDF for a delivery.
     *
     * Returns the stored path relative to the 'local' disk root.
     * Updates the delivery record with path, filename, and size.
     *
     * @throws \RuntimeException if generation or storage fails.
     */
    public function generate(QuoteDelivery $delivery): string
    {
        // ── Priority 1: this delivery already has a valid PDF ─────────────────
        if ($delivery->pdf_path && $this->pdfExists($delivery)) {
            Log::info('quote_delivery.pdf_reused_self', [
                'delivery_id' => $delivery->id,
                'pdf_path'    => $delivery->pdf_path,
            ]);

            return $delivery->pdf_path;
        }

        // ── Priority 2: reuse a PDF from a previous delivery ──────────────────
        $reused = $this->reuseFromPreviousDelivery($delivery);

        if ($reused) {
            return $reused;
        }

        // ── Priority 3: generate fresh ────────────────────────────────────────
        return $this->generateFresh($delivery);
    }

    /**
     * Retrieve the raw PDF content from storage.
     * Used by the email job to attach without re-reading through a temp file.
     */
    public function getContent(QuoteDelivery $delivery): string
    {
        if (! $delivery->pdf_path) {
            throw new \RuntimeException(
                "Delivery #{$delivery->id} has no PDF path recorded."
            );
        }

        return Storage::disk($delivery->pdf_disk ?? 'local')
            ->get($delivery->pdf_path);
    }

    /**
     * Check whether the stored PDF file still physically exists on disk.
     */
    public function pdfExists(QuoteDelivery $delivery): bool
    {
        return ! empty($delivery->pdf_path)
            && Storage::disk($delivery->pdf_disk ?? 'local')
                ->exists($delivery->pdf_path);
    }

    // -------------------------------------------------------------------------
    // Reuse from previous delivery
    // -------------------------------------------------------------------------

    /**
     * Look for a usable PDF from an earlier delivery of the same quote.
     *
     * A previous PDF is considered valid for reuse when ALL of these are true:
     *
     *   a) The previous delivery has a pdf_path recorded.
     *   b) The file physically exists on disk.
     *   c) The PDF was generated AFTER the quote was last updated_at.
     *      This ensures a re-sent quote after edits gets a fresh PDF.
     *
     * When found, the file is copied to this delivery's own storage path
     * so each delivery remains independently auditable.
     *
     * Returns the new path on success, or null if no reusable PDF found.
     */
    private function reuseFromPreviousDelivery(QuoteDelivery $delivery): ?string
    {
        $quote = $delivery->quote;

        // Find the most recent other delivery for this quote that has a PDF.
        $previous = QuoteDelivery::where('quote_id', $quote->id)
            ->where('id', '!=', $delivery->id)
            ->whereNotNull('pdf_path')
            ->whereNotNull('pdf_filename')
            ->latest()
            ->get();

        foreach ($previous as $candidate) {
            // File must physically exist.
            if (! $this->pdfExists($candidate)) {
                continue;
            }

            // Find the generate_pdf attempt that succeeded for this delivery.
            $pdfAttempt = $candidate->attempts()
                ->where('type', QuoteDeliveryAttempt::TYPE_GENERATE_PDF)
                ->where('status', QuoteDeliveryAttempt::STATUS_SUCCEEDED)
                ->latest('completed_at')
                ->first();

            if (! $pdfAttempt?->completed_at) {
                continue;
            }

            // The quote must not have been modified after the PDF was generated.
            // If the quote was edited since, the old PDF may be stale.
            if ($quote->updated_at > $pdfAttempt->completed_at) {
                Log::info('quote_delivery.pdf_reuse_skipped_quote_modified', [
                    'delivery_id'          => $delivery->id,
                    'candidate_id'         => $candidate->id,
                    'quote_updated_at'     => $quote->updated_at->toISOString(),
                    'pdf_generated_at'     => $pdfAttempt->completed_at->toISOString(),
                ]);

                // Quote was edited after this PDF — don't reuse, try next.
                continue;
            }

            // Valid candidate found — copy to this delivery's path.
            $filename = $candidate->pdf_filename;
            $newPath  = "quotes/deliveries/{$delivery->id}/{$filename}";
            $disk     = $candidate->pdf_disk ?? 'local';

            Storage::disk($disk)->copy($candidate->pdf_path, $newPath);

            $size = Storage::disk($disk)->size($newPath);

            $delivery->update([
                'pdf_disk'     => $disk,
                'pdf_path'     => $newPath,
                'pdf_filename' => $filename,
                'pdf_size'     => $size,
            ]);

            Log::info('quote_delivery.pdf_reused_previous', [
                'delivery_id'       => $delivery->id,
                'source_delivery_id' => $candidate->id,
                'quote_id'          => $quote->id,
                'pdf_path'          => $newPath,
                'pdf_generated_at'  => $pdfAttempt->completed_at->toISOString(),
                'quote_updated_at'  => $quote->updated_at->toISOString(),
            ]);

            return $newPath;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Fresh generation
    // -------------------------------------------------------------------------

    /**
     * Generate a new PDF from scratch and store it on disk.
     */
    private function generateFresh(QuoteDelivery $delivery): string
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(120);

        $quote    = $delivery->quote->load(['items.product.category', 'signatures']);
        $data     = $this->buildPdfData($quote);

        $pdf = Pdf::loadView('admin.quotes.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('dpi', 96)
            ->setWarnings(false);

        $content  = $pdf->output();
        $filename = $quote->getPdfFilenameAttribute();
        $path     = "quotes/deliveries/{$delivery->id}/{$filename}";

        Storage::disk('local')->put($path, $content);

        $size = Storage::disk('local')->size($path);

        $delivery->update([
            'pdf_disk'     => 'local',
            'pdf_path'     => $path,
            'pdf_filename' => $filename,
            'pdf_size'     => $size,
        ]);

        Log::info('quote_delivery.pdf_generated_fresh', [
            'delivery_id' => $delivery->id,
            'quote_id'    => $delivery->quote_id,
            'path'        => $path,
            'size_bytes'  => $size,
        ]);

        return $path;
    }

    // -------------------------------------------------------------------------
    // PDF view data builder
    // -------------------------------------------------------------------------

    private function buildPdfData(Quote $quote): array
    {
        $path = fn (string $storagePath) => public_path('storage/' . $storagePath);

        $staticPath = fn (string $publicPath) => public_path($publicPath);

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
                $signatureSrc = $signaturePath;
            }
        }

        $configImages = collect(config('quote.images', []))
            ->map(function ($img) use ($staticPath) {
                $src = $staticPath($img['image']);

                return file_exists($src)
                    ? ['placeholder' => $img['placeholder'], 'src' => $src]
                    : null;
            })
            ->filter()
            ->values();

        $clientLogoSrc = ($quote->logo_url && $exists($quote->logo_url))
            ? $path($quote->logo_url)
            : null;

        $defaultSrc = $staticPath('images/default.png');

        $items = $quote->items->map(function ($item) use ($path, $defaultSrc, $exists) {
            $item->product_image_src =
                ($item->product?->image_url && $exists($item->product->image_url))
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
            'coverSrc'            => $staticPath('images/img.png'),
            'defaultSrc'          => $defaultSrc,
            'closingSrc'          => $staticPath('images/media/image67.jpg'),
            'partnersSrc'         => $staticPath('images/partners_.png'),
            'threeStepRollOutSrc' => $staticPath('images/threestep.jpeg'),
            'clientLogoSrc'       => $clientLogoSrc,
            'configImages'        => $configImages,
            'stageColumns'        => collect(config('quote.stage_columns')),
            'stageAccents'        => ['#fbbf24', '#f97316', '#c2410c'],
            'termsAndConditions'  => $quote->terms_and_conditions
                ?? config('quote.default_terms'),
            'signature' => $signature,
            'signatureSrc' => $signatureSrc
        ];
    }
}

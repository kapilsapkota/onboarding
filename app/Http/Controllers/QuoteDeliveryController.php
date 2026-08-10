<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteDelivery;
use App\Models\QuoteDeliveryAttempt;
use App\Services\Quotes\QuoteDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles delivery-specific actions for a quote.
 *
 * Kept separate from QuoteController to respect single-responsibility:
 *   QuoteController  → CRUD + send dispatch
 *   QuoteDeliveryController → retry, status polling, delivery detail
 */
class QuoteDeliveryController extends Controller
{
    public function __construct(
        private readonly QuoteDeliveryService $deliveryService,
    ) {}

    // -------------------------------------------------------------------------
    // Retry a specific failed attempt
    //
    // POST /admin/quotes/{quote}/deliveries/{delivery}/attempts/{attempt}/retry
    //
    // Authorization:
    //   - User must be authenticated (enforced by route middleware).
    //   - The delivery must belong to this quote (enforced by scopeToQuote()).
    //   - The attempt must belong to this delivery (enforced by scopeToDelivery()).
    //   - The attempt must be in a failed state.
    // -------------------------------------------------------------------------

    public function retryAttempt(
        Request              $request,
        Quote                $quote,
        QuoteDelivery        $delivery,
        QuoteDeliveryAttempt $attempt,
    ): RedirectResponse {
        // Scope guard — ensure delivery belongs to this quote.
        if ($delivery->quote_id !== $quote->id) {
            abort(404);
        }

        // Scope guard — ensure attempt belongs to this delivery.
        if ($attempt->quote_delivery_id !== $delivery->id) {
            abort(404);
        }

        if ($attempt->status !== QuoteDeliveryAttempt::STATUS_FAILED) {
            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with('info', "This operation cannot be retried — current status is '{$attempt->status_label}'.");
        }

        try {
            $this->deliveryService->retryAttempt($attempt);

            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with('success', "Retry queued for '{$attempt->type_label}'. The status will update shortly.");

        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.quotes.show', $quote)
                ->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.quotes.show', $quote)
                ->withErrors(['error' => 'Unable to queue the retry. Please try again.']);
        }
    }

    // -------------------------------------------------------------------------
    // Delivery status poll
    //
    // GET /admin/quotes/{quote}/deliveries/{delivery}/status
    //
    // Returns a lightweight JSON snapshot of the delivery and its attempts.
    // Used by the Livewire delivery panel to poll for updates without
    // reloading the entire page.
    // -------------------------------------------------------------------------

    public function status(
        Request       $request,
        Quote         $quote,
        QuoteDelivery $delivery,
    ): \Illuminate\Http\JsonResponse {
        if ($delivery->quote_id !== $quote->id) {
            abort(404);
        }

        $delivery->load('attempts');

        return response()->json([
            'delivery' => [
                'id'         => $delivery->id,
                'status'     => $delivery->status,
                'status_label' => $delivery->status_label,
                'started_at'   => $delivery->started_at?->toISOString(),
                'completed_at' => $delivery->completed_at?->toISOString(),
                'failed_at'    => $delivery->failed_at?->toISOString(),
            ],
            'attempts' => $delivery->attempts->map(fn ($a) => [
                'id'             => $a->id,
                'type'           => $a->type,
                'type_label'     => $a->type_label,
                'status'         => $a->status,
                'status_label'   => $a->status_label,
                'attempt_number' => $a->attempt_number,
                'started_at'     => $a->started_at?->toISOString(),
                'completed_at'   => $a->completed_at?->toISOString(),
                'failed_at'      => $a->failed_at?->toISOString(),
                'error_message'  => $a->error_message,
                'is_retryable'   => $a->isRetryable(),
            ]),
        ]);
    }
}

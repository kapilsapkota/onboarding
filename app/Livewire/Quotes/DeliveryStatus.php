<?php

namespace App\Livewire\Quotes;

use App\Models\Quote;
use App\Models\QuoteDelivery;
use App\Models\QuoteDeliveryAttempt;
use App\Services\Quotes\QuoteDeliveryService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

class DeliveryStatus extends Component
{
    public Quote $quote;

    // We store only the ID — not the full model — so Livewire's serialisation
    // does not carry heavy attempt data in the component state between polls.
    public ?int $deliveryId = null;

    // Tracks which attempt retry is in flight so we can show a per-button spinner.
    public ?int $retryingAttemptId = null;

    // Flash message shown after a retry is queued.
    public ?string $retryMessage = null;
    public ?string $retryMessageType = null; // 'success' | 'error'

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(Quote $quote): void
    {
        $this->quote = $quote;

        // Boot from the latest delivery if one exists.
        $this->deliveryId = $quote->latestDelivery?->id;
    }

    // -------------------------------------------------------------------------
    // Computed properties
    //
    // #[Computed] caches within a single render cycle but re-reads from the
    // database on every poll tick — exactly what we want.
    // -------------------------------------------------------------------------

    #[Computed]
    public function delivery(): ?QuoteDelivery
    {
        if (! $this->deliveryId) {
            return null;
        }

        return QuoteDelivery::with([
            'attempts' => fn ($q) => $q->orderBy('type')->orderBy('attempt_number'),
            'requestedBy:id,name',
        ])->find($this->deliveryId);
    }

    /**
     * Whether the component should currently be auto-polling.
     * True only while the delivery is in an active state.
     * Once terminal (completed / failed / partially_failed / cancelled),
     * polling stops and the result is permanent.
     */
    #[Computed]
    public function polling(): bool
    {
        $delivery = $this->delivery;

        if (! $delivery) {
            return false;
        }

        return in_array($delivery->status, [
            QuoteDelivery::STATUS_PENDING,
            QuoteDelivery::STATUS_PROCESSING,
        ]);
    }

    /**
     * The most recent attempt per operation type, for display.
     * Returns a keyed collection: ['email' => attempt, 'sms' => attempt, ...]
     */
    #[Computed]
    public function latestAttemptsByType(): \Illuminate\Support\Collection
    {
        $delivery = $this->delivery;

        if (! $delivery) {
            return collect();
        }

        // Group by type, take the highest attempt_number in each group.
        return $delivery->attempts
            ->groupBy('type')
            ->map(fn ($group) => $group->sortByDesc('attempt_number')->first());
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * Retry a specific failed attempt.
     *
     * Authorization: the attempt must belong to a delivery on this quote.
     * This is enforced by checking quote_id on the delivery — not just trusting
     * the attempt ID passed from the browser.
     */
    public function retry(int $attemptId): void
    {
        $this->retryingAttemptId = $attemptId;
        $this->retryMessage      = null;
        $this->retryMessageType  = null;

        try {
            $attempt  = QuoteDeliveryAttempt::with('delivery')->findOrFail($attemptId);
            $delivery = $attempt->delivery;

            // Scope guard — prevent retrying an attempt on a different quote.
            if ($delivery->quote_id !== $this->quote->id) {
                $this->retryMessage     = 'Invalid retry request.';
                $this->retryMessageType = 'error';
                $this->retryingAttemptId = null;
                return;
            }

            if ($attempt->status !== QuoteDeliveryAttempt::STATUS_FAILED) {
                $this->retryMessage     = "This operation cannot be retried — it is currently '{$attempt->status_label}'.";
                $this->retryMessageType = 'error';
                $this->retryingAttemptId = null;
                return;
            }

            app(QuoteDeliveryService::class)->retryAttempt($attempt);

            // Update deliveryId in case user clicked retry on an older delivery.
            $this->deliveryId = $delivery->id;

            $this->retryMessage     = "Retry queued for '{$attempt->type_label}'. Updating...";
            $this->retryMessageType = 'success';

            Log::info('quote_delivery.retry_via_livewire', [
                'quote_id'    => $this->quote->id,
                'delivery_id' => $delivery->id,
                'attempt_id'  => $attemptId,
                'type'        => $attempt->type,
            ]);

        } catch (Throwable $e) {
            report($e);
            $this->retryMessage     = 'Unable to queue the retry. Please try again.';
            $this->retryMessageType = 'error';
        } finally {
            $this->retryingAttemptId = null;
        }
    }

    /**
     * Switch the panel to show a specific delivery (from history).
     */
    public function viewDelivery(int $deliveryId): void
    {
        // Scope guard.
        $exists = QuoteDelivery::where('id', $deliveryId)
            ->where('quote_id', $this->quote->id)
            ->exists();

        if ($exists) {
            $this->deliveryId = $deliveryId;
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): \Illuminate\View\View
    {
        return view('livewire.quotes.delivery-status');
    }
}

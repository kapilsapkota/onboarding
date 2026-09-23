<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Services\StripeBulkChargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeBulkChargeController extends Controller
{
    public function __construct(private StripeBulkChargeService $service)
    {
    }

    /** Displays the customer selection and amount entry form. */
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $customers = StripeCustomer::query()
            ->whereHas('paymentMethods', fn($q) => $q
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
            )
            ->with(['paymentMethods' => fn($q) => $q
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
                ->orderByDesc('is_default')
            ])
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        return view('admin.stripe.bulk-charge', compact('customers', 'search'));
    }

    /** Lists bulk charge batches or individual charges. */
    public function batches(Request $request): View
{
    $view = $request->input('view', 'batches');

    $statuses = StripeChargeBatchItem::query()
        ->whereNotNull('status')
        ->where('status', '!=', '')
        ->distinct()
        ->orderBy('status')
        ->pluck('status');

    /*
     * Normalise status[] input.
     */
    $selectedStatuses = $request->input('status', []);

    if (!is_array($selectedStatuses)) {
        $selectedStatuses = [$selectedStatuses];
    }

    /*
     * ITEM VIEW
     */
    if ($view === 'items') {
        $items = StripeChargeBatchItem::query()
            ->with([
                'batch',
                'stripeCustomer',
                'stripePaymentMethod',
                'payout',
                'balanceTransaction',
            ])

            // Search
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('stripe_payment_intent_id', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%")
                        ->orWhereHas('stripeCustomer', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })

            // Status filter
            ->when(!empty($selectedStatuses), function ($query) use ($selectedStatuses) {
                $query->whereIn('status', $selectedStatuses);
            })

            // Reconciliation filter (reconciled / unreconciled)
            ->when($request->filled('recon') && in_array($request->input('recon'), ['reconciled', 'unreconciled'], true), function ($query) use ($request) {
                $query->where('reconciliation_status', $request->input('recon'));
            })

            ->latest()
            ->paginate(100)
            ->withQueryString();

        return view('admin.stripe.batches', [
            'items' => $items,
            'statuses' => $statuses,
            'selectedStatuses' => $selectedStatuses,
            'selectedRecon' => $request->input('recon', ''),
            'view' => $view,
        ]);
    }

    /*
     * BATCH VIEW
     */
    $batches = StripeChargeBatch::query()
        ->with([
            'items.stripeCustomer',
            'items.stripePaymentMethod',
        ])
        ->withCount([
            'items as succeeded_count' => fn($q) => $q->where('status', 'succeeded'),
            'items as failed_count' => fn($q) => $q->where('status', 'failed'),
            'items as reconciled_count' => fn($q) => $q->where('reconciliation_status', 'reconciled'),
        ])
        ->withSum(['items as succeeded_amount_sum' => fn($q) => $q->where('status', 'succeeded')], 'amount')
        ->withSum(['items as failed_amount_sum' => fn($q) => $q->where('status', 'failed')], 'amount')
        ->withSum(['items as reconciled_net_sum' => fn($q) => $q->where('reconciliation_status', 'reconciled')], 'net_amount')
        ->withSum(['items as reconciled_gross_sum' => fn($q) => $q->where('reconciliation_status', 'reconciled')], 'gross_amount')
        ->withSum(['items as reconciled_fee_sum' => fn($q) => $q->where('reconciliation_status', 'reconciled')], 'fee_amount')
        ->orderByDesc('created_at')
        ->paginate(100)
        ->withQueryString();

    return view('admin.stripe.batches', [
        'batches' => $batches,
        'statuses' => $statuses,
        'selectedStatuses' => $selectedStatuses,
        'view' => $view,
    ]);
}



    /** Confirms and creates the batch - called directly from the review modal. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.stripe_customer_id' => ['required', 'integer'],
            'items.*.stripe_payment_method_id' => ['required', 'integer'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $items = $this->buildValidatedItems($request->input('items'));

        if (empty($items)) {
            return back()->withErrors(['items' => 'No valid items to process.']);
        }

        try {
            $batch = $this->service->createBatch($items, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.stripe.batches.show', $batch)
            ->with('success', "Batch {$batch->reference} created and queued.");
    }

    /** Shows a single batch and its items. */
    public function showBatch(StripeChargeBatch $batch): View
    {
        $batch->load(['items.stripeCustomer', 'items.stripePaymentMethod']);

        return view('admin.stripe.batch-show', compact('batch'));
    }

    /**
     * Validates and normalises the posted items into safe charge records.
     *
     * @return array<int, array{stripe_customer_id: int, stripe_payment_method_id: int, amount: int}>
     */
    private function buildValidatedItems(array $posted): array
    {
        $items = [];

        foreach ($posted as $row) {
            $customerId = (int)($row['stripe_customer_id'] ?? 0);
            $amountCents = (int)($row['amount'] ?? 0);

            if ($amountCents < 1) {
                continue;
            }

            // Never trust a PM ID from the browser - verify ownership server-side
            $customer = StripeCustomer::find($customerId);

            if (!$customer) {
                continue;
            }

            $pm = StripePaymentMethod::where('id', (int)($row['stripe_payment_method_id'] ?? 0))
                ->where('stripe_customer_id', $customer->id)
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
                ->first();

            if (!$pm) {
                continue;
            }

            $items[] = [
                'stripe_customer_id' => $customer->id,
                'stripe_payment_method_id' => $pm->id,
                'amount' => $amountCents,
                'description' => isset($row['description']) ? trim($row['description']) : null,
            ];
        }

        return $items;
    }

    /** Cancels a single charge item and its Stripe PaymentIntent. */
    public function cancel(StripeChargeBatchItem $item): RedirectResponse
    {
        $item->refresh();

        if (in_array($item->status, ['succeeded', 'failed', 'cancelled'], true)) {
            return back()->withErrors([
                'cancel' => 'This charge cannot be cancelled.',
            ]);
        }

        try {
            if ($item->stripe_payment_intent_id) {
                $stripe = new StripeClient(config('services.stripe.secret'));

                $intent = $stripe->paymentIntents->cancel(
                    $item->stripe_payment_intent_id
                );

                $item->update([
                    'status' => 'cancelled',
                    'stripe_data' => $intent->toArray(),
                ]);
            } else {
                $item->update([
                    'status' => 'cancelled',
                ]);
            }

            $item->batch->recalculateStatus();

            return back()->with(
                'success',
                'Charge cancelled successfully.'
            );
        } catch (ApiErrorException $e) {
            return back()->withErrors([
                'cancel' => 'Unable to cancel the Stripe payment: ' . $e->getMessage(),
            ]);
        }
    }

}

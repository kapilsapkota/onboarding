<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeAccount;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Services\StripeAccountResolver;
use App\Services\StripeBulkChargeService;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class StripeBulkChargeController extends Controller
{
    public function __construct(
        private StripeBulkChargeService $service,
        private StripeAccountResolver $accounts,
    ) {}

    /** Displays the customer selection and amount entry form. */
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $selectedAccount = $this->resolveSelectedAccount($request);

        $customers = StripeCustomer::query()
            ->whereHas('paymentMethods', fn ($q) => $q
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
            )
            ->with(['paymentMethods' => fn ($q) => $q
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
                ->orderByDesc('is_default'),
                'stripeAccount.company',
            ])
            ->when($selectedAccount === 'unassigned', fn ($q) => $q->whereNull('stripe_account_id'))
            ->when($selectedAccount instanceof StripeAccount, fn ($q) => $q->where('stripe_account_id', $selectedAccount->id))
            ->when($search, fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        $stripeAccounts = $this->visibleAccounts($request);

        return view('admin.stripe.bulk-charge', compact('customers', 'search', 'stripeAccounts', 'selectedAccount'));
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

        if (! is_array($selectedStatuses)) {
            $selectedStatuses = [$selectedStatuses];
        }

        $stripeAccounts = $this->visibleAccounts($request);
        $selectedAccount = $this->resolveSelectedAccount($request);

        /*
         * ITEM VIEW
         */
        if ($view === 'items') {
            $items = StripeChargeBatchItem::query()
                ->with([
                    'batch',
                    'stripeAccount.company',
                    'stripeCustomer',
                    'stripePaymentMethod',
                    'payout',
                    'balanceTransaction',
                ])
                ->when($selectedAccount === 'unassigned', fn ($q) => $q->whereNull('stripe_account_id'))
                ->when($selectedAccount instanceof StripeAccount, fn ($q) => $q->where('stripe_account_id', $selectedAccount->id))

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
                ->when(! empty($selectedStatuses), function ($query) use ($selectedStatuses) {
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
                'stripeAccounts' => $stripeAccounts,
                'selectedAccount' => $selectedAccount,
            ]);
        }

        /*
         * BATCH VIEW
         */
        $batches = StripeChargeBatch::query()
            ->with([
                'stripeAccount.company',
                'items.stripeCustomer',
                'items.stripePaymentMethod',
            ])
            ->when($selectedAccount === 'unassigned', fn ($q) => $q->whereNull('stripe_account_id'))
            ->when($selectedAccount instanceof StripeAccount, fn ($q) => $q->where('stripe_account_id', $selectedAccount->id))
            ->withCount([
                'items as succeeded_count' => fn ($q) => $q->where('status', 'succeeded'),
                'items as failed_count' => fn ($q) => $q->where('status', 'failed'),
                'items as reconciled_count' => fn ($q) => $q->where('reconciliation_status', 'reconciled'),
            ])
            ->withSum(['items as succeeded_amount_sum' => fn ($q) => $q->where('status', 'succeeded')], 'amount')
            ->withSum(['items as failed_amount_sum' => fn ($q) => $q->where('status', 'failed')], 'amount')
            ->withSum(['items as reconciled_net_sum' => fn ($q) => $q->where('reconciliation_status', 'reconciled')], 'net_amount')
            ->withSum(['items as reconciled_gross_sum' => fn ($q) => $q->where('reconciliation_status', 'reconciled')], 'gross_amount')
            ->withSum(['items as reconciled_fee_sum' => fn ($q) => $q->where('reconciliation_status', 'reconciled')], 'fee_amount')
            ->orderByDesc('created_at')
            ->paginate(100)
            ->withQueryString();

        return view('admin.stripe.batches', [
            'batches' => $batches,
            'statuses' => $statuses,
            'selectedStatuses' => $selectedStatuses,
            'view' => $view,
            'stripeAccounts' => $stripeAccounts,
            'selectedAccount' => $selectedAccount,
        ]);
    }

    /** Confirms and creates the batch - called directly from the review modal. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'stripe_account_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stripe_customer_id' => ['required', 'integer'],
            'items.*.stripe_payment_method_id' => ['required', 'integer'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        // The account comes from our own picker and is re-resolved + checked
        // server-side; ids from the browser never pick credentials.
        $account = $this->resolvePostedAccount($request);

        if ($account) {
            return $this->confirmSingleAccount($account, $request->input('items'));
        }

        return $this->confirmMultiAccount($request->input('items'));
    }

    /**
     * Strict per-account confirm: every item must belong to the picked account.
     */
    private function confirmSingleAccount(StripeAccount $account, array $posted): RedirectResponse
    {
        $items = $this->buildValidatedItems($posted, $account->id, $this->accounts->legacyAccount()?->id, true);

        if (empty($items)) {
            return back()->withErrors(['items' => 'No valid items to process for this Stripe account.']);
        }

        try {
            $batch = $this->service->createBatch($items, auth()->id(), $account->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        Activity::record(
            description: "Created bulk charge batch {$batch->reference} on {$account->display_name} ({$batch->customer_count} customers, {$batch->total_amount}c)",
            subject: $batch,
            event: 'bulk-charge-confirm',
            properties: ['reference' => $batch->reference, 'stripe_account_id' => $account->id],
            logName: 'stripe',
        );

        return redirect()
            ->route('admin.stripe.batches.show', $batch)
            ->with('success', "Batch {$batch->reference} created and queued on {$account->display_name}.");
    }

    /**
     * All-accounts confirm: validate loosely, then split into one batch per
     * account pool so each batch charges through the right credentials.
     * Unscoped rows form the legacy batch charged with the global keys.
     */
    private function confirmMultiAccount(array $posted): RedirectResponse
    {
        $items = $this->buildValidatedItems($posted, null, null, false);

        if (empty($items)) {
            return back()->withErrors(['items' => 'No valid items to process.']);
        }

        $groups = collect($items)->groupBy(fn ($item) => $item['stripe_account_id'] ?? 'unassigned');

        try {
            $batches = [];

            foreach ($groups as $key => $groupItems) {
                $groupAccountId = $key === 'unassigned' ? null : (int) $key;
                $batches[] = $this->service->createBatch($groupItems->all(), auth()->id(), $groupAccountId);
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        $refs = collect($batches)->map->reference->join(', ');

        Activity::record(
            description: 'Created '.count($batches)." bulk charge batches (one per Stripe account): {$refs}.",
            event: 'bulk-charge-confirm',
            properties: ['references' => collect($batches)->map->reference->all()],
            logName: 'stripe',
        );

        if (count($batches) === 1) {
            $batch = $batches[0];

            return redirect()
                ->route('admin.stripe.batches.show', $batch)
                ->with('success', "Batch {$batch->reference} created and queued.");
        }

        return redirect()
            ->route('admin.stripe.batches.index')
            ->with('success', count($batches)." batches created and queued (one per Stripe account): {$refs}.");
    }

    /** Shows a single batch and its items. */
    public function showBatch(StripeChargeBatch $batch): View
    {
        $batch->load(['items.stripeCustomer', 'items.stripePaymentMethod', 'stripeAccount.company']);

        return view('admin.stripe.batch-show', compact('batch'));
    }

    /**
     * Validates and normalises the posted items into safe charge records.
     *
     * Strict mode (explicit account picked): every customer/payment-method
     * pair must belong to it; legacy rows only match when the picked account
     * is the legacy one. Loose mode (all accounts): any active BECS pair is
     * accepted and each item carries its own account id for per-pool batching.
     *
     * @return array<int, array{stripe_customer_id: int, stripe_payment_method_id: int, amount: int, stripe_account_id: ?int}>
     */
    private function buildValidatedItems(array $posted, ?int $accountId, ?int $legacyId, bool $strict): array
    {
        $items = [];

        foreach ($posted as $row) {
            $customerId = (int) ($row['stripe_customer_id'] ?? 0);
            $amountCents = (int) ($row['amount'] ?? 0);

            if ($amountCents < 1) {
                continue;
            }

            // Never trust a PM ID from the browser - verify ownership server-side
            $customer = StripeCustomer::find($customerId);

            if (! $customer) {
                continue;
            }

            if ($strict && ! $this->accountMatches($customer->stripe_account_id, $accountId, $legacyId)) {
                continue;
            }

            $pmQuery = StripePaymentMethod::where('id', (int) ($row['stripe_payment_method_id'] ?? 0))
                ->where('stripe_customer_id', $customer->id)
                ->where('type', 'au_becs_debit')
                ->where('status', 'active');

            if ($strict) {
                $pmQuery->where(function ($q) use ($accountId, $legacyId) {
                    // Null rows only match legacy mode; real accounts match exactly.
                    if ($accountId === null || $accountId === $legacyId) {
                        $q->whereNull('stripe_account_id')->orWhere('stripe_account_id', $legacyId);
                    } else {
                        $q->where('stripe_account_id', $accountId);
                    }
                });
            }

            $pm = $pmQuery->first();

            if (! $pm) {
                continue;
            }

            $items[] = [
                'stripe_customer_id' => $customer->id,
                'stripe_payment_method_id' => $pm->id,
                'amount' => $amountCents,
                'description' => isset($row['description']) ? trim($row['description']) : null,
                'stripe_account_id' => $customer->stripe_account_id,
            ];
        }

        return $items;
    }

    private function accountMatches(?int $rowAccountId, ?int $accountId, ?int $legacyId): bool
    {
        if ($accountId !== null) {
            return $rowAccountId === $accountId;
        }

        return $rowAccountId === null || $rowAccountId === $legacyId;
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

        $stripe = $this->accounts->clientFor(
            $item->stripeAccount ?? $item->batch?->stripeAccount
        );

        try {
            if ($item->stripe_payment_intent_id) {
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

            Activity::record(
                description: "Cancelled bulk charge item #{$item->id} ({$item->amount}c)",
                subject: $item,
                event: 'cancel',
                logName: 'stripe',
            );

            return back()->with(
                'success',
                'Charge cancelled successfully.'
            );
        } catch (ApiErrorException $e) {
            return back()->withErrors([
                'cancel' => 'Unable to cancel the Stripe payment: '.$e->getMessage(),
            ]);
        }
    }

    private function visibleAccounts(Request $request): Collection
    {
        return $this->accounts->visibleAccounts($request->user());
    }

    private function assertAccountVisible(Request $request, StripeAccount $account): void
    {
        $this->accounts->assertAccountVisible($request->user(), $account);
    }

    /**
     * Returns 'all', 'unassigned', or the resolved account for the index filter.
     */
    private function resolveSelectedAccount(Request $request): string|StripeAccount
    {
        $filter = $request->input('stripe_account', 'all');

        if ($filter === 'unassigned' || $filter === 'all') {
            return $filter;
        }

        if (! is_numeric($filter)) {
            return 'all';
        }

        $account = $this->accounts->forAccount((int) $filter);
        $this->assertAccountVisible($request, $account);

        return $account;
    }

    /**
     * Resolves the posted account for confirm(), or null for legacy mode.
     * Non-numeric values fall back to legacy mode (old clients).
     */
    private function resolvePostedAccount(Request $request): ?StripeAccount
    {
        $posted = $request->input('stripe_account_id');

        if ($posted === null || $posted === '') {
            return null;
        }

        if (! is_numeric($posted)) {
            return null;
        }

        $account = $this->accounts->forAccount((int) $posted);
        $this->assertAccountVisible($request, $account);

        return $account;
    }
}

<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Stripe Payout - {{ $payout->stripe_payout_id }}
            </h2>
            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                {{ $payout->stripeAccount?->display_name ?? 'Legacy pool' }}{{ $payout->stripeAccount?->company ? ' — '.$payout->stripeAccount->company->name : '' }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        <x-alert></x-alert>

        {{-- BACK --}}
        <div class="mb-5">
            <a href="{{ route('admin.payouts.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to payouts
            </a>
        </div>

        @php
            $statusConfig = [
                'paid' => [
                    'bg' => 'bg-green-50 border-green-200',
                    'text' => 'text-green-800',
                    'sub' => 'text-green-600',
                    'label' => '✓ Paid',
                ],
                'pending' => [
                    'bg' => 'bg-yellow-50 border-yellow-200',
                    'text' => 'text-yellow-800',
                    'sub' => 'text-yellow-600',
                    'label' => '⏳ Pending',
                ],
                'in_transit' => [
                    'bg' => 'bg-blue-50 border-blue-200',
                    'text' => 'text-blue-800',
                    'sub' => 'text-blue-600',
                    'label' => '🚚 In Transit',
                ],
                'failed' => [
                    'bg' => 'bg-red-50 border-red-200',
                    'text' => 'text-red-800',
                    'sub' => 'text-red-600',
                    'label' => '✕ Failed',
                ],
                'canceled' => [
                    'bg' => 'bg-gray-50 border-gray-200',
                    'text' => 'text-gray-800',
                    'sub' => 'text-gray-600',
                    'label' => 'Canceled',
                ],
                'default' => [
                    'bg' => 'bg-gray-50 border-gray-200',
                    'text' => 'text-gray-800',
                    'sub' => 'text-gray-600',
                    'label' => ucfirst($payout->status ?? 'unknown'),
                ],
            ];

            $status = $statusConfig[$payout->status] ?? $statusConfig['default'];

            /*
             * Full-payout charge totals come from the controller ($summary),
             * which sums ALL balance transactions excl. Stripe's own
             * `payout` debit row. The page-only sums below are just for the
             * currently visible rows (JS live-filter updates them).
             */
            $transactionData = $transactions->items() ?? [];

            $transactionCount = count($transactionData);

            $gross = $summary['charges_gross'] ?? collect($transactionData)->sum(fn ($t) => $t->amount ?? 0);

            $fees = $summary['charges_fees'] ?? collect($transactionData)->sum(fn ($t) => $t->fee ?? 0);

            $total = $summary['charges_net'] ?? collect($transactionData)->sum(fn ($t) => $t->net ?? 0);

            $fullCount = $summary['charges_count'] ?? $transactions->total();

            $currency = strtoupper($payout->currency ?? 'aud');

            $batchItems = $batchItems ?? collect();

            $itemsSummary = $itemsSummary ?? [
                'count' => $batchItems->count(),
                'gross' => $batchItems->sum('gross_amount'),
                'fees' => $batchItems->sum('fee_amount'),
                'net' => $batchItems->sum('net_amount'),
            ];

            $reconciliationStatus = $payout->reconciliation_status ?? null;

            $canListTransactions = $canListTransactions ?? ($transactions->total() > 0);

            $canShowTransactions = $canListTransactions
                ?? ($reconciliationStatus === 'completed');

            $transactionMessage = $transactionMessage ?? null;
        @endphp


        {{-- =========================================================
             PAYOUT STATUS
        ========================================================== --}}

        <div class="rounded-lg border p-5 mb-6 {{ $status['bg'] }}">

            <div class="flex justify-between items-center flex-wrap gap-5">

                <div>

                    <div class="text-xl font-semibold {{ $status['text'] }}">
                        {{ $status['label'] }}
                    </div>

                    <div class="mt-2 text-sm {{ $status['sub'] }}">
                        Created:
                        {{ optional($payout->stripe_created_at)->format('d/m/Y H:i') ?? '—' }}
                    </div>

                    @if($payout->arrival_at)

                        <div class="text-sm {{ $status['sub'] }}">
                            Arrival:
                            {{ $payout->arrival_at->format('d/m/Y') }}
                        </div>

                    @endif

                    @if($payout->failure_message)

                        <div class="mt-2 text-sm text-red-600">
                            {{ $payout->failure_message }}
                        </div>

                    @endif

                </div>


                <div class="text-right">

                    <div class="text-3xl font-bold {{ $status['text'] }}">

                        {{ $currency }}
                        {{ number_format($payout->amount / 100, 2) }}

                    </div>

                    <div class="text-sm {{ $status['sub'] }}">
                        {{ ucfirst($payout->method ?? '-') }}
                    </div>

                </div>

            </div>

        </div>


        {{-- =========================================================
             PAYOUT DETAILS
        ========================================================== --}}

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">

            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">

                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    Payout Details
                </h3>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 p-6">

                <div>

                    <div class="text-sm text-gray-500">
                        Payout ID
                    </div>

                    <div class="mt-1 font-mono text-sm break-all">
                        {{ $payout->stripe_payout_id }}
                    </div>

                </div>


                <div>

                    <div class="text-sm text-gray-500">
                        Type
                    </div>

                    <div class="mt-1 font-semibold">
                        {{ ucfirst($payout->type ?? '-') }}
                    </div>

                </div>


                <div>

                    <div class="text-sm text-gray-500">
                        Method
                    </div>

                    <div class="mt-1 font-semibold">
                        {{ ucfirst($payout->method ?? '-') }}
                    </div>

                </div>


                <div>

                    <div class="text-sm text-gray-500">
                        Currency
                    </div>

                    <div class="mt-1 font-semibold">
                        {{ $currency }}
                    </div>

                </div>

            </div>

        </div>


        {{-- =========================================================
             TRANSACTIONS NOT AVAILABLE
        ========================================================== --}}

        @if(!$canShowTransactions)

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">

                <div class="flex gap-4">

                    <div class="flex-shrink-0">

                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">

                            <span class="text-blue-600 text-lg">
                                i
                            </span>

                        </div>

                    </div>


                    <div>

                        <h3 class="font-semibold text-blue-900">
                            Transactions aren't available for this payout
                        </h3>


                        <p class="mt-1 text-sm text-blue-700">

                            @if(($payout->method ?? null) === 'instant')

                                Instant payouts settle immediately, so Stripe
                                doesn't attach individual balance transactions
                                to them. There is nothing to break down here.

                            @elseif($reconciliationStatus === 'in_progress')

                                Stripe is still reconciling this payout.
                                The associated balance transactions may become
                                available once reconciliation is complete.

                            @elseif($transactionMessage)

                                {{ $transactionMessage }}

                            @else

                                Stripe does not provide the individual
                                balance transactions for this payout.

                            @endif

                        </p>

                    </div>

                </div>

            </div>

        @else



            {{-- =====================================================
                 SUMMARY (charge lines only, excl. payout debit)
            ====================================================== --}}

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">

                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">

                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        Summary
                    </h3>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-gray-50 dark:bg-gray-900">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Type
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Count
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Gross
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Fees
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Total
                            </th>

                        </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                        <tr>

                            <td class="px-6 py-5">

                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ ucfirst($payout->type ?? '-') }}
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $currency }}
                                </div>

                            </td>


                            <td id="summaryCount" class="px-6 py-5 text-right font-semibold">
                                {{ $fullCount }}
                            </td>
                            <td class="px-6 py-5 text-right">
                                <span id="summaryGross">{{ $currency }}
                                {{ number_format($gross / 100, 2) }}</span>
                                <div class="text-xs text-gray-400 font-normal">excl. payout debit</div>
                            </td>


                            <td class="px-6 py-5 text-right text-red-600">

                                <span id="summaryFees">-{{ $currency }}
                                {{ number_format($fees / 100, 2) }}</span>

                            </td>


                            <td class="px-6 py-5 text-right font-bold">

                                <span id="summaryTotal">{{ $currency }}
                                {{ number_format($total / 100, 2) }}</span>

                            </td>

                        </tr>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">

                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-2">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        Charge Items
                        <span class="ml-2 text-xs font-normal text-gray-500">
                            bulk-charge items reconciled into this payout
                        </span>
                    </h3>
                    @if($batchItems->isNotEmpty())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">
                            {{ $itemsSummary['count'] }} item{{ $itemsSummary['count'] === 1 ? '' : 's' }}
                            · G {{ $currency }} {{ number_format(($itemsSummary['gross'] ?? 0) / 100, 2) }}
                            · F {{ $currency }} {{ number_format(($itemsSummary['fees'] ?? 0) / 100, 2) }}
                            · N {{ $currency }} {{ number_format(($itemsSummary['net'] ?? 0) / 100, 2) }}
                        </span>
                    @endif
                </div>

                @if($batchItems->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Batch / Item</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Gross</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Net</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($batchItems as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4">
                                        @if($item->batch)
                                            <a href="{{ route('admin.stripe.batches.show', $item->batch) }}"
                                               class="font-mono text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ $item->batch->reference }} #{{ $item->id }}
                                            </a>
                                        @else
                                            <span class="font-mono text-sm text-gray-500">Item #{{ $item->id }}</span>
                                        @endif
                                        @if($item->stripe_payment_intent_id)
                                            <div class="text-xs text-gray-400 font-mono mt-1">{{ $item->stripe_payment_intent_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ $item->stripeCustomer?->name ?? $item->stripeCustomer?->email ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right tabular-nums">
                                        {{ $currency }} {{ number_format($item->amount / 100, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right tabular-nums">
                                        @if($item->gross_amount !== null)
                                            {{ $currency }} {{ number_format($item->gross_amount / 100, 2) }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right tabular-nums">
                                        @if($item->fee_amount !== null && $item->fee_amount > 0)
                                            <span class="text-red-600">-{{ $currency }} {{ number_format($item->fee_amount / 100, 2) }}</span>
                                        @elseif($item->fee_amount !== null)
                                            {{ $currency }} {{ number_format($item->fee_amount / 100, 2) }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold tabular-nums">
                                        @if($item->net_amount !== null)
                                            {{ $currency }} {{ number_format($item->net_amount / 100, 2) }}
                                        @else
                                            <span class="text-gray-400 font-normal">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ ucfirst($item->status) }}
                                        @if($item->isReconciled())
                                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">reconciled</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-6 text-sm text-gray-500">
                        No bulk-charge items reconciled into this payout — its lines are invoice direct debits or external (non-app) transactions.
                    </div>
                @endif

            </div>


            {{-- =====================================================
                 TRANSACTIONS
            ====================================================== --}}

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">

                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">

                    <div class="flex justify-between items-center gap-4 flex-wrap">

                        <div>

                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                Transactions
                            </h3>

                            <p class="text-sm text-gray-500 mt-1">

                                <span id="transactionCountValue">{{ $transactionCount }}</span>

                                <span id="transactionCountLabel">{{ $transactionCount === 1 ? 'transaction' : 'transactions' }}</span>

                            </p>

                        </div>


<div class="flex gap-2 flex-wrap">

    <input
        id="transactionSearch"
        type="text"
        placeholder="Search..."
        class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"
    >

    <select
        id="transactionScope"
        class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"
    >
        <option value="">
            All transactions
        </option>

        <option value="app">
            App transactions only
        </option>

        <option value="external">
            Non-app transactions
        </option>
    </select>

    <select
        id="typeFilter"
        class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"
    >

        <option value="">
            All types
        </option>

        <option value="charge">
            Charge
        </option>

        <option value="payment">
            Payment
        </option>

        <option value="refund">
            Refund
        </option>

        <option value="adjustment">
            Adjustment
        </option>

        <option value="fee">
            Fee
        </option>

        <option value="stripe_fee">
            Stripe Fee
        </option>

        <option value="transfer">
            Transfer
        </option>

        <option value="payout">
            Payout
        </option>

        <option value="other">
            Other
        </option>

    </select>

</div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                        <thead class="bg-gray-50 dark:bg-gray-900">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Type
                            </th>


                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Gross
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Fee
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Total
                            </th>

                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Batch Item
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Description
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Date
                            </th>

                        </tr>

                        </thead>


                        <tbody
                            id="transactionsTable"
                            class="divide-y divide-gray-200 dark:divide-gray-700"
                        >

                        @forelse($transactionData as $transaction)

                            @php

                                $type = $transaction->type ?? 'unknown';

                                $amount = $transaction->amount ?? 0;

                                $fee = $transaction->fee ?? 0;

                                $net = $transaction->net ?? 0;

                                $created = $transaction->occurred_at
                                    ?? $transaction->available_at
                                    ?? now();

                                $badge = match($type) {

                                    'charge',
                                    'payment'
                                        => 'bg-green-100 text-green-700',

                                    'refund',
                                    'dispute',
                                    'dispute_loss'
                                        => 'bg-red-100 text-red-700',

                                    'adjustment'
                                        => 'bg-yellow-100 text-yellow-700',

                                    'fee',
                                    'stripe_fee'
                                        => 'bg-purple-100 text-purple-700',

                                    'transfer'
                                        => 'bg-blue-100 text-blue-700',

                                    'payout'
                                        => 'bg-gray-100 text-gray-700',

                                    default
                                        => 'bg-gray-100 text-gray-700',
                                };

                                $searchText = strtolower(
                                    ($transaction->stripe_balance_transaction_id ?? '') . ' ' .
                                    ($transaction->description ?? '') . ' ' .
                                    ($transaction->customer_name ?? '') . ' ' .
                                    ($transaction->customer_email ?? '') . ' ' .
                                    $type
                                );

                            @endphp


                            <tr
                                    class="transaction-row hover:bg-gray-50 dark:hover:bg-gray-700"
                                    data-search="{{ $searchText }}"
                                    data-type="{{ strtolower($type) }}"
                                    data-app="{{ !empty($transaction->is_app_transaction_flag) ? '1' : '0' }}"
                                    data-gross="{{ $amount }}"
                                    data-fee="{{ $fee }}"
                                    data-net="{{ $net }}"
                                >

                                <td class="px-6 py-5 whitespace-nowrap">

                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}"
                                    >

                                        {{ ucfirst(str_replace('_', ' ', $type)) }}

                                    </span>

                                </td>

                                <td class="px-6 py-5 text-right whitespace-nowrap">

                                    <div class="font-medium">

                                        {{ $currency }}
                                        {{ number_format($amount / 100, 2) }}

                                    </div>

                                </td>

                                <td class="px-6 py-5 text-right whitespace-nowrap">

                                    @if($fee > 0)

                                        <span class="text-red-600">

                                            -{{ $currency }}
                                            {{ number_format($fee / 100, 2) }}

                                        </span>

                                    @else

                                        <span class="text-gray-400">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- TOTAL --}}

                                <td class="px-6 py-5 text-right whitespace-nowrap">

                                    <span class="font-semibold">

                                        {{ $currency }}
                                        {{ number_format($net / 100, 2) }}

                                    </span>

                                </td>
                                <td class="px-6 py-5 text-center whitespace-nowrap">

                                    @if(!empty($transaction->customer_name))

                                        <div class="font-medium">

                                            {{ $transaction->customer_name }}

                                        </div>

                                        @if(!empty($transaction->customer_email))

                                            <div class="text-xs text-gray-500">

                                                {{ $transaction->customer_email }}

                                            </div>

                                        @endif

                                        @if(!empty($transaction->customer_stripe_id))

                                            <div class="mt-1 text-xs font-mono text-gray-400">

                                                {{ $transaction->customer_stripe_id }}

                                            </div>

                                        @endif

                                    @else

                                        N/A

                                    @endif

                                </td>


                                {{-- DESCRIPTION + INVOICE --}}

                                {{-- BATCH ITEM (gross/fee/net from stripe_charge_batch_items) --}}

                                <td class="px-6 py-5 whitespace-nowrap">
                                    @if($transaction->batchItem)
                                        @if($transaction->batchItem->batch)
                                            <a href="{{ route('admin.stripe.batches.show', $transaction->batchItem->batch) }}"
                                               class="font-mono text-xs text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ $transaction->batchItem->batch->reference }} #{{ $transaction->batchItem->id }}
                                            </a>
                                        @else
                                            <span class="font-mono text-xs text-gray-500">Item #{{ $transaction->batchItem->id }}</span>
                                        @endif
                                        <div class="mt-1 text-xs tabular-nums text-gray-600">
                                            @if($transaction->batchItem->gross_amount !== null)
                                                G {{ $currency }} {{ number_format($transaction->batchItem->gross_amount / 100, 2) }}
                                                · <span class="text-red-600">F {{ $currency }} {{ number_format($transaction->batchItem->fee_amount / 100, 2) }}</span>
                                                · <span class="font-semibold">N {{ $currency }} {{ number_format($transaction->batchItem->net_amount / 100, 2) }}</span>
                                            @else
                                                <span class="text-gray-400">unreconciled</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ ucfirst($transaction->batchItem->status ?? '') }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>

                                <td class="px-6 py-5">

                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">

                                        {{ $transaction->description ?? '—' }}
                                    </div>

                                    <div class="mt-1 text-xs font-mono text-gray-500">

                                        {{ $transaction->stripe_balance_transaction_id }}

                                    </div>

                                </td>


                                {{-- DATE --}}

                                <td class="px-6 py-5 whitespace-nowrap">

                                    <div class="text-sm">

                                        {{ $created->format('d/m/Y') }}

                                    </div>

                                    <div class="text-xs text-gray-500">

                                        {{ $created->format('H:i') }}

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="px-6 py-12 text-center text-gray-500"
                                >

                                    No transactions were found for this payout.

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- =================================================
                     PAGINATION
                ================================================== --}}

                @if($transactions->total() > 0)

                    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">

                        <div class="flex items-center justify-between">

                            <div class="text-sm text-gray-500">

                                Showing

                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
                                </span>

                                of

                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ $transactions->total() }}
                                </span>

                                transactions

                            </div>


                            <div>
                                {{ $transactions->links() }}
                            </div>

                        </div>

                    </div>

                @endif

            </div>

        @endif

    </div>


    {{-- =============================================================
         SEARCH / FILTER
    ============================================================== --}}

<script>

    document.addEventListener('DOMContentLoaded', function () {

        const search = document.getElementById('transactionSearch');
        const typeFilter = document.getElementById('typeFilter');
        const scopeFilter = document.getElementById('transactionScope');

        const currency = @json($currency);
        const summaryCount = document.getElementById('summaryCount');
        const summaryGross = document.getElementById('summaryGross');
        const summaryFees = document.getElementById('summaryFees');
        const summaryTotal = document.getElementById('summaryTotal');
        const transactionCountValue = document.getElementById('transactionCountValue');
        const transactionCountLabel = document.getElementById('transactionCountLabel');

        if (!search || !typeFilter || !scopeFilter) {
            return;
        }

        function money(cents) {
            return currency + ' ' + (cents / 100).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function updateSummary() {

            let count = 0;
            let gross = 0;
            let fees = 0;
            let total = 0;

            document.querySelectorAll('.transaction-row').forEach(row => {

                if (row.style.display === 'none') {
                    return;
                }

                count++;
                gross += parseInt(row.dataset.gross || '0', 10);
                fees += parseInt(row.dataset.fee || '0', 10);
                total += parseInt(row.dataset.net || '0', 10);
            });

            if (summaryCount) {
                summaryCount.textContent = count;
            }

            if (summaryGross) {
                summaryGross.textContent = money(gross);
            }

            if (summaryFees) {
                summaryFees.textContent = '-' + money(fees);
            }

            if (summaryTotal) {
                summaryTotal.textContent = money(total);
            }

            if (transactionCountValue) {
                transactionCountValue.textContent = count;
            }

            if (transactionCountLabel) {
                transactionCountLabel.textContent = count === 1 ? 'transaction' : 'transactions';
            }
        }

        function filterRows() {

            const term = search.value.toLowerCase().trim();
            const type = typeFilter.value.toLowerCase();
            const scope = scopeFilter.value;

            document.querySelectorAll('.transaction-row').forEach(row => {

                const text = row.textContent
                    .toLowerCase()
                    .replace(/\s+/g, ' ')
                    .trim();

                const rowType = row.dataset.type || '';
                const isAppTransaction = row.dataset.app === '1';

                const matchesSearch =
                    !term || text.includes(term);

                const matchesType =
                    !type || rowType === type;

                const matchesScope =
                    !scope ||
                    (scope === 'app' && isAppTransaction) ||
                    (scope === 'external' && !isAppTransaction);

                row.style.display =
                    matchesSearch &&
                    matchesType &&
                    matchesScope
                        ? ''
                        : 'none';
            });

            updateSummary();
        }

        search.addEventListener('input', filterRows);

        typeFilter.addEventListener('change', filterRows);

        scopeFilter.addEventListener('change', filterRows);

    });

</script>

</x-app-layout>

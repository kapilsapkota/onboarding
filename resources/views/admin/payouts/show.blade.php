<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Stripe Payout - {{ $payout->id }}
            </h2>
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
                    'label' => ucfirst($payout->status),
                ],
            ];

            $status = $statusConfig[$payout->status] ?? $statusConfig['default'];

            /*
             * Transactions can be null when Stripe does not expose
             * individual transactions for this payout.
             */
            $transactionData = $transactions?->data ?? [];

            $transactionCount = count($transactionData);

            $gross = collect($transactionData)->sum(function ($transaction) {
                return $transaction->amount ?? 0;
            });

            $fees = collect($transactionData)->sum(function ($transaction) {
                return $transaction->fee ?? 0;
            });

            $total = collect($transactionData)->sum(function ($transaction) {
                return $transaction->net ?? 0;
            });

            $currency = strtoupper($payout->currency);

            $reconciliationStatus = $payout->reconciliation_status ?? null;

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
                        {{ \Carbon\Carbon::createFromTimestamp($payout->created)->format('d/m/Y H:i') }}
                    </div>

                    @if($payout->arrival_date)

                        <div class="text-sm {{ $status['sub'] }}">
                            Arrival:
                            {{ \Carbon\Carbon::createFromTimestamp($payout->arrival_date)->format('d/m/Y') }}
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
                        {{ ucfirst($payout->method) }}
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
                        {{ $payout->id }}
                    </div>

                </div>


                <div>

                    <div class="text-sm text-gray-500">
                        Type
                    </div>

                    <div class="mt-1 font-semibold">
                        {{ ucfirst($payout->type) }}
                    </div>

                </div>


                <div>

                    <div class="text-sm text-gray-500">
                        Method
                    </div>

                    <div class="mt-1 font-semibold">
                        {{ ucfirst($payout->method) }}
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

                            @if($reconciliationStatus === 'in_progress')

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
                 SUMMARY
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
                                    {{ ucfirst($payout->type) }}
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $currency }}
                                </div>

                            </td>


                            <td class="px-6 py-5 text-right font-semibold">
                                {{ $transactionCount }}
                            </td>


                            <td class="px-6 py-5 text-right">

                                {{ $currency }}
                                {{ number_format($gross / 100, 2) }}

                            </td>


                            <td class="px-6 py-5 text-right text-red-600">

                                -{{ $currency }}
                                {{ number_format($fees / 100, 2) }}

                            </td>


                            <td class="px-6 py-5 text-right font-bold">

                                {{ $currency }}
                                {{ number_format($total / 100, 2) }}

                            </td>

                        </tr>

                        </tbody>

                    </table>

                </div>

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

                                {{ $transactionCount }}

                                {{ $transactionCount === 1 ? 'transaction' : 'transactions' }}

                            </p>

                        </div>


                        <div class="flex gap-2">

                            <input
                                id="transactionSearch"
                                type="text"
                                placeholder="Search..."
                                class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"
                            >


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

                                $created = \Carbon\Carbon::createFromTimestamp(
                                    $transaction->created
                                );

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
                                    ($transaction->id ?? '') . ' ' .
                                    ($transaction->description ?? '') . ' ' .
                                    $type
                                );

                            @endphp


                            <tr
                                class="transaction-row hover:bg-gray-50 dark:hover:bg-gray-700"
                                data-search="{{ $searchText }}"
                                data-type="{{ strtolower($type) }}"
                            >

                                {{-- TYPE --}}

                                <td class="px-6 py-5 whitespace-nowrap">

                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}"
                                    >

                                        {{ ucfirst(str_replace('_', ' ', $type)) }}

                                    </span>

                                </td>


                                {{-- GROSS --}}

                                <td class="px-6 py-5 text-right whitespace-nowrap">

                                    <div class="font-medium">

                                        {{ $currency }}
                                        {{ number_format($amount / 100, 2) }}

                                    </div>

                                </td>


                                {{-- FEE --}}

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


                                {{-- DESCRIPTION --}}

                                <td class="px-6 py-5">

                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">

                                        {{ $transaction->description ?? '—' }}

                                    </div>

                                    <div class="mt-1 text-xs font-mono text-gray-500">

                                        {{ $transaction->id }}

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
                                    colspan="6"
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

                @if($transactions && count($transactions->data))

                    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">

                        <div class="flex items-center justify-between">

                            <div class="text-sm text-gray-500">

                                Showing

                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ count($transactions->data) }}
                                </span>

                                transactions

                            </div>


                            <div class="flex gap-3">

                                @if(request()->filled('starting_after'))

                                    <a
                                        href="{{ request()->fullUrlWithQuery([
                                            'starting_after' => null,
                                            'ending_before' => null,
                                        ]) }}"
                                        class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                    >
                                        ← Previous
                                    </a>

                                @endif


                                @if($transactions->has_more)

                                    <a
                                        href="{{ request()->fullUrlWithQuery([
                                            'starting_after' => $transactions->data[count($transactions->data) - 1]->id,
                                            'ending_before' => null,
                                        ]) }}"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                    >
                                        Next →
                                    </a>

                                @endif

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


            if (!search || !typeFilter) {
                return;
            }


            function filterRows() {

                const term = search.value.toLowerCase().trim();

                const type = typeFilter.value.toLowerCase();


                document.querySelectorAll('.transaction-row').forEach(row => {

                    const text = row.dataset.search || '';

                    const rowType = row.dataset.type || '';


                    const matchesSearch =
                        !term || text.includes(term);


                    const matchesType =
                        !type || rowType === type;


                    row.style.display =
                        matchesSearch && matchesType
                            ? ''
                            : 'none';

                });

            }


            search.addEventListener('input', filterRows);

            typeFilter.addEventListener('change', filterRows);

        });

    </script>

</x-app-layout>

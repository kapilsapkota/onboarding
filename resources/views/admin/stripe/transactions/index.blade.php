<x-app-layout>

    <x-slot name="header">

        <div class="flex justify-between items-center">

            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Stripe Transactions
            </h2>

        </div>

    </x-slot>


    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        <x-alert></x-alert>


        {{-- =========================================================
             FILTERS
        ========================================================== --}}

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">

            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">

                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    Search & Filter
                </h3>

            </div>


            <form
                method="GET"
                action="{{ route('admin.stripe.transactions.index') }}"
                class="p-6"
            >

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">


                    {{-- SEARCH --}}

                    <div class="md:col-span-2">

                        <label class="text-sm text-gray-500">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Transaction ID, description, source..."
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                        >

                    </div>


                    {{-- TYPE --}}

                    <div>

                        <label class="text-sm text-gray-500">
                            Type
                        </label>

                        <select
                            name="type"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                        >

                            <option value="">
                                All
                            </option>

                            <option value="charge" @selected($type === 'charge')>
                                Charge
                            </option>

                            <option value="payment" @selected($type === 'payment')>
                                Payment
                            </option>

                            <option value="refund" @selected($type === 'refund')>
                                Refund
                            </option>

                            <option value="adjustment" @selected($type === 'adjustment')>
                                Adjustment
                            </option>

                            <option value="stripe_fee" @selected($type === 'stripe_fee')>
                                Stripe Fee
                            </option>

                            <option value="application_fee" @selected($type === 'application_fee')>
                                Application Fee
                            </option>

                            <option value="transfer" @selected($type === 'transfer')>
                                Transfer
                            </option>

                            <option value="payout" @selected($type === 'payout')>
                                Payout
                            </option>

                            <option value="dispute" @selected($type === 'dispute')>
                                Dispute
                            </option>

                            <option value="other" @selected($type === 'other')>
                                Other
                            </option>

                        </select>

                    </div>


                    {{-- FROM --}}

                    <div>

                        <label class="text-sm text-gray-500">
                            From
                        </label>

                        <input
                            type="date"
                            name="created_from"
                            value="{{ $createdFrom }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                        >

                    </div>


                    {{-- TO --}}

                    <div>

                        <label class="text-sm text-gray-500">
                            To
                        </label>

                        <input
                            type="date"
                            name="created_to"
                            value="{{ $createdTo }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                        >

                    </div>

                </div>


                <div class="mt-5 flex gap-3">

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        Search
                    </button>


                    <a
                        href="{{ route('admin.stripe.transactions.index') }}"
                        class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        {{-- =========================================================
             SUMMARY
        ========================================================== --}}

        @php

            $gross = $transactions->sum(function ($transaction) {
                return $transaction->amount ?? 0;
            });

            $fees = $transactions->sum(function ($transaction) {
                return $transaction->fee ?? 0;
            });

            $net = $transactions->sum(function ($transaction) {
                return $transaction->net ?? 0;
            });

            $currency = strtoupper(
                $transactions->first()->currency ?? 'aud'
            );

        @endphp


        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">


            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Transactions
                </div>

                <div class="mt-2 text-2xl font-bold">
                    {{ $transactions->count() }}
                </div>

            </div>


            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Gross
                </div>

                <div class="mt-2 text-2xl font-bold">

                    {{ $currency }}
                    {{ number_format($gross / 100, 2) }}

                </div>

            </div>


            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Fees
                </div>

                <div class="mt-2 text-2xl font-bold text-red-600">

                    -{{ $currency }}
                    {{ number_format($fees / 100, 2) }}

                </div>

            </div>


            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Net
                </div>

                <div class="mt-2 text-2xl font-bold">

                    {{ $currency }}
                    {{ number_format($net / 100, 2) }}

                </div>

            </div>

        </div>


        {{-- =========================================================
             TRANSACTIONS
        ========================================================== --}}

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">

                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    Transactions
                </h3>

            </div>


            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-50 dark:bg-gray-900">

                    <tr>

                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            Type
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            ID
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            Description
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                            Gross
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                            Fee
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                            Net
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                            Date
                        </th>

                    </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                    @forelse($transactions as $transaction)

                        @php

                            $type = $transaction->type ?? 'unknown';

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

                                'stripe_fee',
                                'application_fee'
                                    => 'bg-purple-100 text-purple-700',

                                'transfer'
                                    => 'bg-blue-100 text-blue-700',

                                default
                                    => 'bg-gray-100 text-gray-700',
                            };

                        @endphp


                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">


                            {{-- TYPE --}}

                            <td class="px-6 py-5 whitespace-nowrap">

                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}"
                                >

                                    {{ ucfirst(str_replace('_', ' ', $type)) }}

                                </span>

                            </td>


                            {{-- ID --}}

                            <td class="px-6 py-5 whitespace-nowrap">

                                <a
                                    href="{{ route('admin.stripe.transactions.show', $transaction->id) }}"
                                    class="font-mono text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    {{ $transaction->id }}
                                </a>

                            </td>


                            {{-- DESCRIPTION --}}

                            <td class="px-6 py-5">

                                <div class="text-sm font-medium">

                                    {{ $transaction->description ?? '—' }}

                                </div>

                            </td>


                            {{-- GROSS --}}

                            <td class="px-6 py-5 text-right whitespace-nowrap">

                                {{ strtoupper($transaction->currency) }}

                                {{ number_format(($transaction->amount ?? 0) / 100, 2) }}

                            </td>


                            {{-- FEE --}}

                            <td class="px-6 py-5 text-right whitespace-nowrap">

                                @if(($transaction->fee ?? 0) > 0)

                                    <span class="text-red-600">

                                        -{{ strtoupper($transaction->currency) }}

                                        {{ number_format($transaction->fee / 100, 2) }}

                                    </span>

                                @else

                                    <span class="text-gray-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- NET --}}

                            <td class="px-6 py-5 text-right whitespace-nowrap font-semibold">

                                {{ strtoupper($transaction->currency) }}

                                {{ number_format(($transaction->net ?? 0) / 100, 2) }}

                            </td>


                            {{-- DATE --}}

                            <td class="px-6 py-5 whitespace-nowrap">

                                <div class="text-sm">

                                    {{ \Carbon\Carbon::createFromTimestamp($transaction->created)->format('d/m/Y') }}

                                </div>

                                <div class="text-xs text-gray-500">

                                    {{ \Carbon\Carbon::createFromTimestamp($transaction->created)->format('H:i') }}

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-gray-500"
                            >

                                No transactions found.

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =====================================================
                 PAGINATION
            ====================================================== --}}

            @if($transactions->count())

                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div class="text-sm text-gray-500">

                            Showing
                            <span class="font-medium">
                                {{ $transactions->count() }}
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


                            @if($hasMore)

                                <a
                                    href="{{ request()->fullUrlWithQuery([
                                        'starting_after' => $lastId,
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

    </div>

</x-app-layout>

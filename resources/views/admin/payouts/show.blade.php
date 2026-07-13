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



        {{-- STATUS BANNER --}}
        @php
            $statusConfig = [
                'paid' => [
                    'bg' => 'bg-green-50 border-green-200',
                    'text' => 'text-green-800',
                    'sub' => 'text-green-600',
                    'label' => '✓ Paid'
                ],
                'pending' => [
                    'bg' => 'bg-yellow-50 border-yellow-200',
                    'text' => 'text-yellow-800',
                    'sub' => 'text-yellow-600',
                    'label' => '⏳ Pending'
                ],
                'in_transit' => [
                    'bg' => 'bg-blue-50 border-blue-200',
                    'text' => 'text-blue-800',
                    'sub' => 'text-blue-600',
                    'label' => '🚚 In Transit'
                ],
                'failed' => [
                    'bg' => 'bg-red-50 border-red-200',
                    'text' => 'text-red-800',
                    'sub' => 'text-red-600',
                    'label' => '✕ Failed'
                ],
                'default' => [
                    'bg' => 'bg-gray-50 border-gray-200',
                    'text' => 'text-gray-800',
                    'sub' => 'text-gray-600',
                    'label' => ucfirst($payout->status)
                ],
            ];

            $status = $statusConfig[$payout->status] ?? $statusConfig['default'];
        @endphp


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

                        {{ strtoupper($payout->currency) }}

                        {{ number_format($payout->amount / 100, 2) }}

                    </div>


                    <div class="text-sm {{ $status['sub'] }}">

                        {{ ucfirst($payout->method) }}

                    </div>

                </div>

            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">


            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Payout ID
                </div>

                <div class="mt-2 font-mono text-sm break-all">
                    {{ $payout->id }}
                </div>

            </div>



            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Transactions
                </div>

                <div class="mt-2 text-2xl font-bold">
                    {{ count($transactions) }}
                </div>

            </div>




            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Type
                </div>

                <div class="mt-2 font-semibold">
                    {{ ucfirst($payout->type) }}
                </div>

            </div>



            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">

                <div class="text-sm text-gray-500">
                    Currency
                </div>

                <div class="mt-2 font-semibold">
                    {{ strtoupper($payout->currency) }}
                </div>

            </div>


        </div>


        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-5">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>

                    <label class="text-sm text-gray-500">
                        Search
                    </label>

                    <input
                        id="transactionSearch"
                        type="text"
                        placeholder="Search ID, description..."
                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                    >

                </div>

                <div>

                    <label class="text-sm text-gray-500">
                        Transaction Type
                    </label>


                    <select
                        id="typeFilter"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                    >
                        <option value="">All</option>

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

                        <option value="payout">
                            Payout
                        </option>

                        <option value="payout_cancel">
                            Payout Cancel
                        </option>

                        <option value="stripe_fee">
                            Stripe Fee
                        </option>

                        <option value="application_fee">
                            Application Fee
                        </option>

                        <option value="application_fee_refund">
                            Application Fee Refund
                        </option>

                        <option value="transfer">
                            Transfer
                        </option>

                        <option value="transfer_cancel">
                            Transfer Cancel
                        </option>

                        <option value="transfer_refund">
                            Transfer Refund
                        </option>

                        <option value="dispute">
                            Dispute
                        </option>

                        <option value="dispute_loss">
                            Dispute Loss
                        </option>

                        <option value="dispute_reversal">
                            Dispute Reversal
                        </option>

                        <option value="network_cost">
                            Network Cost
                        </option>

                        <option value="connect_collection_transfer">
                            Connect Collection Transfer
                        </option>

                        <option value="other">
                            Other
                        </option>

                    </select>


                </div>

                <div>

                    <label class="text-sm text-gray-500">
                        Sort
                    </label>


                    <select
                        id="sortTransactions"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                    >

                        <option value="">
                            Default
                        </option>

                        <option value="amount_desc">
                            Amount High → Low
                        </option>


                        <option value="amount_asc">
                            Amount Low → High
                        </option>


                    </select>


                </div>

            </div>

        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">


            <div class="px-6 py-4 border-b dark:border-gray-700">

                <h3 class="font-semibold">
                    Balance Transactions
                </h3>

            </div>



            <div class="overflow-x-auto">


                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">


                    <thead class="bg-gray-50 dark:bg-gray-900">

                    <tr>

                        <th class="px-4 py-3 text-left text-xs">
                            Date
                        </th>


                        <th class="px-4 py-3 text-left text-xs">
                            Type
                        </th>


                        <th class="px-4 py-3 text-left text-xs">
                            Description
                        </th>


                        <th class="px-4 py-3 text-right text-xs">
                            Amount
                        </th>


                        <th class="px-4 py-3 text-right text-xs">
                            Fee
                        </th>


                        <th class="px-4 py-3 text-right text-xs">
                            Net
                        </th>


                        <th class="px-4 py-3 text-right text-xs">
                            Action
                        </th>


                    </tr>


                    </thead>



                    <tbody
                        id="transactionsTable"
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >

                    @forelse($transactions as $transaction)

                        @php
                            $source = $transaction->source ?? null;

                            $type = $transaction->type ?? 'unknown';

                            $created = \Carbon\Carbon::createFromTimestamp(
                                $transaction->created
                            );

                            $amount = $transaction->amount / 100;
                            $fee = $transaction->fee / 100;
                            $net = $transaction->net / 100;

                            $customer = null;

                            if ($source && isset($source->customer)) {
                                $customer = $source->customer;
                            }

                        @endphp


                        <tr
                            class="transaction-row hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            data-search="
        {{ strtolower(
            ($transaction->id ?? '') . ' ' .
            ($transaction->description ?? '') . ' ' .
            ($type ?? '') . ' ' .
            ($customer->email ?? '')
        ) }}
    "
                            data-type="{{ strtolower($type) }}"
                            data-amount="{{ $amount }}"
                        >


                            {{-- DATE --}}
                            <td class="px-4 py-4 whitespace-nowrap">

                                <div class="text-sm">

                                    {{ $created->format('d/m/Y') }}

                                </div>

                                <div class="text-xs text-gray-500">

                                    {{ $created->format('H:i') }}

                                </div>

                            </td>





                            {{-- TYPE --}}
                            <td class="px-4 py-4">


                                @php
                                    $badge = match($type) {
                                        'charge' => 'bg-green-100 text-green-700',
                                        'refund' => 'bg-red-100 text-red-700',
                                        'adjustment' => 'bg-yellow-100 text-yellow-700',
                                        'fee' => 'bg-purple-100 text-purple-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                @endphp


                                <span
                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}"
                                >

            {{ ucfirst(str_replace('_',' ', $type)) }}

        </span>


                            </td>





                            {{-- DESCRIPTION --}}
                            <td class="px-4 py-4">


                                <div class="text-sm font-medium">

                                    {{ $transaction->description ?? '-' }}

                                </div>



                                <div class="mt-1 text-xs text-gray-500 font-mono">

                                    {{ $transaction->id }}

                                </div>



                                @if($source)

                                    <div class="mt-1 text-xs text-gray-500">

                                        Source:

                                        <span class="font-mono">


                </span>

                                    </div>

                                @endif


                            </td>






                            {{-- AMOUNT --}}
                            <td class="px-4 py-4 text-right">


                                <div class="font-semibold">

                                    {{ strtoupper($transaction->currency) }}

                                    {{ number_format($amount,2) }}

                                </div>


                            </td>






                            {{-- FEE --}}
                            <td class="px-4 py-4 text-right text-red-600">


                                -

                                {{ strtoupper($transaction->currency) }}

                                {{ number_format($fee,2) }}


                            </td>






                            {{-- NET --}}
                            <td class="px-4 py-4 text-right font-semibold">


                                {{ strtoupper($transaction->currency) }}

                                {{ number_format($net,2) }}


                            </td>







                            {{-- ACTION --}}
                            <td class="px-4 py-4 text-right">


                                <button
                                    type="button"
                                    class="view-transaction text-indigo-600 hover:text-indigo-800 text-sm"
                                    data-id="{{ $transaction->id }}"
                                >

                                    View

                                </button>


                            </td>



                        </tr>





                        {{-- EXPANDED DETAIL ROW --}}

                        <tr
                            id="detail-{{ $transaction->id }}"
                            class="hidden bg-gray-50 dark:bg-gray-900"
                        >

                            <td colspan="7" class="px-6 py-5">


                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">


                                    <div>

                                        <div class="text-gray-500">
                                            Transaction ID
                                        </div>

                                        <div class="font-mono break-all">

                                            {{ $transaction->id }}

                                        </div>

                                    </div>



                                    <div>

                                        <div class="text-gray-500">
                                            Available On
                                        </div>

                                        <div>

                                            @if($transaction->available_on)

                                                {{ \Carbon\Carbon::createFromTimestamp($transaction->available_on)->format('d/m/Y') }}

                                            @else
                                                -
                                            @endif

                                        </div>


                                    </div>





                                    @if($customer)

                                        <div>

                                            <div class="text-gray-500">
                                                Customer
                                            </div>


                                            <div class="font-medium">

                                                {{ $customer->name ?? 'Unknown' }}

                                            </div>


                                            <div class="text-xs">

                                                {{ $customer->email ?? '' }}

                                            </div>


                                        </div>

                                    @endif




                                </div>




                                @if($source)

                                    <div class="mt-5">

                                        <div class="text-gray-500 text-sm">
                                            Stripe Object
                                        </div>


                                        <pre
                                            class="mt-2 bg-black text-green-400 rounded p-3 text-xs overflow-auto"
                                        >{{ json_encode($source, JSON_PRETTY_PRINT) }}</pre>


                                    </div>

                                @endif



                            </td>

                        </tr>



                    @empty


                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-10 text-center text-gray-500"
                            >

                                No transactions found.

                            </td>

                        </tr>


                    @endforelse


                    </tbody>


                </table>

            </div>

            @if(count($transactions->data))
                <div class="mt-6 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex flex-1 justify-between sm:hidden">

                        @if(request()->filled('starting_after'))
                            <a href="{{ url()->current() }}"
                               class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Previous
                            </a>
                        @endif

                        @if($transactions->has_more)
                            <a href="{{ request()->fullUrlWithQuery([
                    'starting_after' => last($transactions->data)->id
                ]) }}"
                               class="relative ml-auto inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Next
                            </a>
                        @endif

                    </div>

                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ count($transactions->data) }}</span>
                            transactions
                        </p>

                        <div class="flex gap-3">

                            @if(request()->filled('starting_after'))
                                <a href="{{ url()->current() }}"
                                   class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    ← Previous
                                </a>
                            @endif

                            @if($transactions->has_more)
                                <a href="{{ request()->fullUrlWithQuery([
                        'starting_after' => last($transactions->data)->id
                    ]) }}"
                                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Next →
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const search = document.getElementById('transactionSearch');
            const typeFilter = document.getElementById('typeFilter');
            const sort = document.getElementById('sortTransactions');
            const table = document.getElementById('transactionsTable');

            function filterRows() {
                const term = search.value.toLowerCase();
                const type = typeFilter.value.toLowerCase();

                document.querySelectorAll('.transaction-row').forEach(row => {
                    const text = row.dataset.search;
                    const rowType = row.dataset.type;

                    const matchesSearch = text.includes(term);
                    const matchesType = !type || rowType === type;

                    row.style.display = matchesSearch && matchesType ? '' : 'none';

                    const detail = document.getElementById(
                        'detail-' + row.querySelector('.view-transaction').dataset.id
                    );

                    if (detail && row.style.display === 'none') {
                        detail.style.display = 'none';
                    }
                });
            }


            search.addEventListener('input', filterRows);
            typeFilter.addEventListener('change', filterRows);


            sort.addEventListener('change', function () {

                const rows = Array.from(
                    document.querySelectorAll('.transaction-row')
                );

                const value = this.value;

                if (!value) return;

                rows.sort((a,b) => {

                    const amountA = parseFloat(a.dataset.amount);
                    const amountB = parseFloat(b.dataset.amount);

                    if(value === 'amount_desc') {
                        return amountB - amountA;
                    }

                    if(value === 'amount_asc') {
                        return amountA - amountB;
                    }

                });

                rows.forEach(row => {

                    const detail = document.getElementById(
                        'detail-' + row.querySelector('.view-transaction').dataset.id
                    );

                    table.appendChild(row);

                    if(detail) {
                        table.appendChild(detail);
                    }

                });

            });


            document.querySelectorAll('.view-transaction')
                .forEach(button => {

                    button.addEventListener('click', function () {

                        const id = this.dataset.id;

                        const detail = document.getElementById(
                            'detail-' + id
                        );

                        detail.classList.toggle('hidden');

                        this.textContent =
                            detail.classList.contains('hidden')
                                ? 'View'
                                : 'Hide';

                    });

                });


        });
    </script>

</x-app-layout>

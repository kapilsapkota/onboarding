<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Bulk Charge Batches
            </h2>
            <a href="{{ route('admin.stripe.bulk-charge') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow-sm hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Bulk Charge
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <div
            class="inline-flex rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 shadow-sm">
            <a href="{{ route('admin.stripe.batches.index', ['view' => 'batches']) }}"
               class="px-4 py-2 text-sm font-medium rounded-lg transition
                    {{ $view === 'batches'
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                Batches
            </a>

            <a href="{{ route('admin.stripe.batches.index', ['view' => 'items']) }}"
               class="px-4 py-2 text-sm font-medium rounded-lg transition
                    {{ $view === 'items'
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                Individual Charges
            </a>
        </div>
    </div>

    @if ($view === 'items')

<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-visible">

    {{-- FILTER BAR --}}
    <div class="relative z-50 p-4 bg-gray-50/70 dark:bg-gray-900/40 border-b border-gray-200 dark:border-gray-700 overflow-visible rounded-t-2xl">

        <form
            id="chargeItemFilterForm"
            method="GET"
            action="{{ route('admin.stripe.batches.index') }}"
            class="flex flex-wrap items-center gap-3"
        >
            <input type="hidden" name="view" value="items">

            {{-- SEARCH --}}
            <div class="flex-1 min-w-[280px] relative">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <input
                    id="chargeItemSearch"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search customer, email, description, payment intent..."
                    autocomplete="off"
                    class="w-full rounded-xl border-gray-200 dark:border-gray-600
                           dark:bg-gray-700 dark:text-white text-sm pl-10
                           focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            {{-- STATUS MULTI SELECT --}}
            <div class="relative">

                {{-- STATUS BUTTON --}}
                <button
                    type="button"
                    id="statusFilterButton"
                    class="inline-flex items-center justify-between gap-3
                           min-w-[170px] px-4 py-2
                           rounded-xl border border-gray-200
                           dark:border-gray-600
                           bg-white dark:bg-gray-700
                           text-sm text-gray-700 dark:text-gray-200 shadow-sm
                           hover:border-gray-300 dark:hover:border-gray-500"
                >
                    <span id="statusFilterLabel">
                        @if(count($selectedStatuses))
                            {{ count($selectedStatuses) }} selected
                        @else
                            All statuses
                        @endif
                    </span>

                    <svg
                        class="w-4 h-4 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>

                {{-- STATUS DROPDOWN --}}
                <div
                    id="statusFilterDropdown"
                    class="hidden absolute right-0 top-full z-[9999] mt-2
                           w-64
                           bg-white dark:bg-gray-800
                           border border-gray-200 dark:border-gray-700
                           rounded-xl shadow-xl"
                >

                    {{-- STATUS SEARCH --}}
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                        <input
                            type="text"
                            id="statusFilterSearch"
                            placeholder="Search status..."
                            autocomplete="off"
                            class="w-full rounded-lg
                                   border-gray-300 dark:border-gray-600
                                   dark:bg-gray-700 dark:text-white
                                   text-sm
                                   focus:border-indigo-500
                                   focus:ring-indigo-500"
                        >
                    </div>

                    {{-- SELECT ALL --}}
                    <div class="p-2">
                        <label
                            class="flex items-center gap-2 px-2 py-2
                                   rounded-lg cursor-pointer
                                   hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <input
                                type="checkbox"
                                id="statusSelectAll"
                                class="rounded border-gray-300
                                       text-indigo-600
                                       focus:ring-indigo-500"
                            >

                            <span class="text-sm font-medium
                                         text-gray-700 dark:text-gray-200">
                                Select All
                            </span>
                        </label>
                    </div>

                    {{-- STATUS OPTIONS --}}
                    <div class="max-h-64 overflow-y-auto px-2 pb-2">

                        @foreach($statuses as $status)

                            <label
                                class="status-option flex items-center gap-2
                                       px-2 py-2 rounded-lg cursor-pointer
                                       hover:bg-gray-50
                                       dark:hover:bg-gray-700"
                                data-status="{{ strtolower($status) }}"
                            >
                                <input
                                    type="checkbox"
                                    name="status[]"
                                    value="{{ $status }}"
                                    class="status-checkbox rounded
                                           border-gray-300
                                           text-indigo-600
                                           focus:ring-indigo-500"
                                    @checked(
                                        in_array(
                                            $status,
                                            $selectedStatuses,
                                            true
                                        )
                                    )
                                >

                                <span class="text-sm
                                             text-gray-700
                                             dark:text-gray-200">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </span>
                            </label>

                        @endforeach

                    </div>

                    {{-- FOOTER --}}
                    <div
                        class="flex justify-between items-center gap-2 p-3
                               border-t border-gray-200
                               dark:border-gray-700"
                    >

                        <button
                            type="button"
                            id="statusClear"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg
                                   bg-gray-100 text-gray-700
                                   hover:bg-gray-200
                                   dark:bg-gray-700
                                   dark:text-gray-200"
                        >
                            Clear
                        </button>

                        <button
                            type="button"
                            id="statusApply"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg
                                   bg-indigo-600 text-white
                                   hover:bg-indigo-700"
                        >
                            Apply
                        </button>

                    </div>

                </div>
            </div>

            {{-- RECONCILIATION FILTER --}}
            <div>
                <select
                    name="recon"
                    onchange="document.getElementById('chargeItemFilterForm').submit()"
                    class="rounded-xl border-gray-200 dark:border-gray-600
                           dark:bg-gray-700 dark:text-white text-sm shadow-sm
                           focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="" @selected(($selectedRecon ?? '') === '')>
                        All reconciliations
                    </option>
                    <option value="reconciled" @selected(($selectedRecon ?? '') === 'reconciled')>
                        Reconciled
                    </option>
                    <option value="unreconciled" @selected(($selectedRecon ?? '') === 'unreconciled')>
                        Unreconciled
                    </option>
                </select>
            </div>

            {{-- CLEAR ALL FILTERS --}}
            @if(request()->hasAny(['search', 'status', 'recon']))
                <a
                    href="{{ route('admin.stripe.batches.index', ['view' => 'items']) }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium
                           rounded-xl hover:bg-gray-200
                           dark:bg-gray-700
                           dark:text-gray-200"
                >
                    Clear
                </a>
            @endif

        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto">

        <table class="min-w-full divide-y
                      divide-gray-200 dark:divide-gray-700">

            <thead class="bg-gray-50 dark:bg-gray-900/60">

            <tr>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Customer
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Batch
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Payment Method
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Description
                </th>

                <th class="px-5 py-3.5 text-right text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Amount
                </th>

                <th class="px-5 py-3.5 text-right text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Gross
                </th>

                <th class="px-5 py-3.5 text-right text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Fee
                </th>

                <th class="px-5 py-3.5 text-right text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Net
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Reconciliation
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Payout
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Status
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Payment Intent
                </th>

                <th class="px-5 py-3.5 text-left text-[11px]
                           font-semibold text-gray-500 uppercase tracking-wider">
                    Error
                </th>

            </tr>

            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">

            @forelse($items as $item)

                @php
                    $itemBadge = match($item->status) {
                        'succeeded' => ['dot' => 'bg-green-500', 'bg' => 'bg-green-50', 'text' => 'text-green-700', 'ring' => 'ring-green-600/20'],
                        'processing' => ['dot' => 'bg-blue-500', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'ring' => 'ring-blue-600/20'],
                        'failed' => ['dot' => 'bg-red-500', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-600/20'],
                        default => ['dot' => 'bg-gray-400', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'ring' => 'ring-gray-500/20'],
                    };
                    $initials = strtoupper(collect(preg_split('/\s+/', trim($item->stripeCustomer->name ?? $item->stripeCustomer->email ?? '?')))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
                @endphp

                <tr class="hover:bg-indigo-50/40 dark:hover:bg-gray-700/50 transition">

                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline-flex w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold items-center justify-center shrink-0">
                                {{ $initials }}
                            </span>
                            <span>
                                <span class="block font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item->stripeCustomer->name
                                        ?? $item->stripeCustomer->email
                                        ?? '-' }}
                                </span>

                                @if(
                                    $item->stripeCustomer?->email &&
                                    $item->stripeCustomer?->name
                                )
                                    <span class="block text-xs text-gray-400">
                                        {{ $item->stripeCustomer->email }}
                                    </span>
                                @endif
                            </span>
                        </div>

                    </td>

                    <td class="px-5 py-4">

                        <a
                            href="{{ route(
                                'admin.stripe.batches.show',
                                $item->batch
                            ) }}"
                            class="font-mono text-sm
                                   text-indigo-600 hover:text-indigo-800 hover:underline"
                        >
                            {{ $item->batch->reference }}
                        </a>

                    </td>

                    <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 font-mono text-xs text-gray-600 dark:text-gray-300">
                            {{ $item->stripePaymentMethod->maskedLabel() }}
                        </span>
                    </td>

                    <td class="px-5 py-4 text-sm text-gray-500 max-w-[220px] truncate" title="{{ $item->description }}">
                        {{ $item->description ?? '-' }}
                    </td>

                    <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                        {{ $item->formattedAmount() }}
                    </td>

                    <td class="px-5 py-4 text-right tabular-nums text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        {{ $item->formattedGross() ?? '—' }}
                    </td>

                    <td class="px-5 py-4 text-right tabular-nums whitespace-nowrap">
                        @if($item->formattedFee() !== null && $item->fee_amount > 0)
                            <span class="text-red-600">-{{ $item->formattedFee() }}</span>
                        @else
                            <span class="text-gray-400">{{ $item->formattedFee() ?? '—' }}</span>
                        @endif
                    </td>

                    <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100 whitespace-nowrap">
                        {{ $item->formattedNet() ?? '—' }}
                    </td>

                    <td class="px-5 py-4">
                        @if($item->isReconciled())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Reconciled
                            </span>
                            @if($item->reconciled_at)
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $item->reconciled_at->format('d M Y g:i A') }}
                                </div>
                            @endif
                            @if($item->stripe_balance_transaction_id)
                                <div class="font-mono text-xs text-gray-400 mt-1">
                                    {{ $item->stripe_balance_transaction_id }}
                                </div>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                Unreconciled
                            </span>
                        @endif
                    </td>

                    <td class="px-5 py-4">
                        @if($item->payout)
                            <a
                                href="{{ route('admin.payouts.show', $item->payout->stripe_payout_id) }}"
                                class="font-mono text-xs text-indigo-600 hover:text-indigo-800 hover:underline"
                            >
                                {{ $item->payout->stripe_payout_id }}
                            </a>
                            <div class="font-bold text-sm mt-1 whitespace-nowrap tabular-nums">
                                {{ strtoupper($item->payout->currency) }} {{ $item->payout->formatted_amount }}
                            </div>
                            @if($item->payout->arrival_at)
                                <div class="text-xs text-gray-400">
                                    Arrives {{ $item->payout->arrival_at->format('d M Y') }}
                                </div>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="px-5 py-4">

                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs
                                   font-semibold ring-1 ring-inset
                                   {{ $itemBadge['bg'] }}
                                   {{ $itemBadge['text'] }}
                                   {{ $itemBadge['ring'] }}"
                        >
                            <span class="w-1.5 h-1.5 rounded-full {{ $itemBadge['dot'] }}"></span>
                            {{ ucfirst($item->status) }}
                        </span>

                    </td>

                    <td class="px-5 py-4 font-mono text-xs text-gray-400">
                        {{ $item->stripe_payment_intent_id ?? '-' }}
                    </td>

                    <td class="px-5 py-4 text-xs text-red-500 max-w-[220px] truncate" title="{{ $item->error_message }}">
                        {{ $item->error_message ?? '-' }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="13"
                        class="py-14 text-center"
                    >
                        <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6M9 8h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        </svg>
                        <p class="mt-3 text-gray-500">No charges found.</p>
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    {{-- PAGINATION --}}
    @if ($items->hasPages())

        <div
            class="px-5 py-4 border-t bg-gray-50/70 dark:bg-gray-900/40
                   border-gray-200 dark:border-gray-700 rounded-b-2xl"
        >
            {{ $items->links() }}
        </div>

    @endif

</div>


@else
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                    <tr>
                        <th class="px-5 py-3.5 w-8"></th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Customers</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Succeeded</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Failed</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Reconciled</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Completed</th>
                        <th></th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">

                    @forelse ($batches as $batch)

                        @php
                            $badge = match($batch->status) {
                                'completed'             => ['dot' => 'bg-green-500', 'bg' => 'bg-green-50', 'text' => 'text-green-700', 'ring' => 'ring-green-600/20', 'label' => 'Completed'],
                                'processing'            => ['dot' => 'bg-blue-500', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'ring' => 'ring-blue-600/20', 'label' => 'Processing'],
                                'completed_with_errors' => ['dot' => 'bg-yellow-500', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'ring' => 'ring-yellow-600/20', 'label' => 'Completed with Errors'],
                                'failed'                => ['dot' => 'bg-red-500', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-600/20', 'label' => 'Failed'],
                                default                 => ['dot' => 'bg-gray-400', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'ring' => 'ring-gray-500/20', 'label' => ucfirst($batch->status)],
                            };
                            $reconPct = ($batch->customer_count ?? 0) > 0
                                ? min(100, round((($batch->reconciled_count ?? 0) / $batch->customer_count) * 100))
                                : 0;
                        @endphp

                        {{-- Batch row --}}
                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-gray-700/50 transition cursor-pointer"
                            onclick="toggleItems({{ $batch->id }})">

                            {{-- EXPAND TOGGLE --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 items-center justify-center text-gray-400">
                                    <svg id="chevron-{{ $batch->id }}"
                                         class="w-4 h-4 transition-transform duration-200"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </td>

                            {{-- REFERENCE --}}
                            <td class="px-5 py-4">
                                <div class="font-mono font-semibold text-sm text-gray-900 dark:text-gray-100">{{ $batch->reference }}</div>
                                <div class="text-xs text-gray-400">{{ strtoupper($batch->currency) }}</div>
                            </td>

                            {{-- CREATED --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $batch->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $batch->created_at->format('g:i A') }}</div>
                            </td>

                            {{-- CUSTOMERS --}}
                            <td class="px-5 py-4">
                                <div class="font-medium tabular-nums">{{ number_format($batch->customer_count) }}</div>
                            </td>

                            {{-- TOTAL --}}
                            <td class="px-5 py-4">
                                <div class="font-bold text-lg tabular-nums text-gray-900 dark:text-gray-100">{{ $batch->formattedTotal() }}</div>
                            </td>

                            {{-- SUCCEEDED --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-semibold text-green-700 tabular-nums">
                                    {{ number_format($batch->succeeded_count ?? 0) }} · ${{ number_format(($batch->succeeded_amount_sum ?? 0) / 100, 2) }}
                                </div>
                            </td>

                            {{-- FAILED --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if(($batch->failed_count ?? 0) > 0)
                                    <div class="font-semibold text-red-600 tabular-nums">
                                        {{ number_format($batch->failed_count) }} · ${{ number_format(($batch->failed_amount_sum ?? 0) / 100, 2) }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>

                            {{-- RECONCILED (gross / fee / net received) --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-medium text-sm tabular-nums">
                                    {{ number_format($batch->reconciled_count ?? 0) }} of {{ number_format($batch->customer_count) }}
                                </div>
                                <div class="mt-1.5 w-28 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full {{ $reconPct === 100 ? 'bg-green-500' : 'bg-indigo-500' }}" style="width: {{ $reconPct }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1.5 tabular-nums">
                                    Gross ${{ number_format(($batch->reconciled_gross_sum ?? 0) / 100, 2) }}
                                </div>
                                <div class="text-xs text-red-500 tabular-nums">
                                    Fee -${{ number_format(($batch->reconciled_fee_sum ?? 0) / 100, 2) }}
                                </div>
                                <div class="text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">
                                    Net ${{ number_format(($batch->reconciled_net_sum ?? 0) / 100, 2) }}
                                </div>
                            </td>

                            {{-- STATUS --}}
                            <td class="px-5 py-4">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['ring'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                {{ $badge['label'] }}
                            </span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-medium text-sm text-gray-700 dark:text-gray-200">{{ $batch->createdBy->name ?? '' }}</div>
                            </td>

                            {{-- COMPLETED --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($batch->completed_at)
                                    <div class="text-sm text-gray-700 dark:text-gray-200">{{ $batch->completed_at->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $batch->completed_at->format('g:i A') }}</div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>

                            {{-- ACTION --}}
                            <td class="px-5 py-4 text-right" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.stripe.batches.show', $batch) }}"
                                   class="inline-flex items-center px-3 py-2 text-sm font-medium bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-100 transition">
                                    View
                                </a>
                            </td>

                        </tr>

                        {{-- Line items row (hidden by default) --}}
                        <tr id="items-{{ $batch->id }}" class="hidden">
                            <td colspan="12" class="px-0 py-0 bg-gray-50/70 dark:bg-gray-900/40">

                                <table class="min-w-full text-sm">
                                    <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="pl-16 pr-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Payment Method
                                        </th>
                                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th class="px-5 py-2.5 text-right text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Payment Intent
                                        </th>
                                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                            Error
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @foreach ($batch->items as $item)
                                        @php
                                            $itemBadge = match($item->status) {
                                                'succeeded'  => ['dot' => 'bg-green-500', 'bg' => 'bg-green-50', 'text' => 'text-green-700', 'ring' => 'ring-green-600/20'],
                                                'processing' => ['dot' => 'bg-blue-500', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'ring' => 'ring-blue-600/20'],
                                                'failed'     => ['dot' => 'bg-red-500', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-600/20'],
                                                default      => ['dot' => 'bg-gray-400', 'bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'ring' => 'ring-gray-500/20'],
                                            };
                                        @endphp
                                        <tr class="hover:bg-white dark:hover:bg-gray-700/50 transition">
                                            <td class="pl-16 pr-5 py-3">
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $item->stripeCustomer->name ?? $item->stripeCustomer->email ?? '-' }}
                                                </span>
                                                @if ($item->stripeCustomer->email && $item->stripeCustomer->name)
                                                    <span
                                                        class="block text-xs text-gray-400">{{ $item->stripeCustomer->email }}</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 font-mono text-xs text-gray-600 dark:text-gray-300">
                                                    {{ $item->stripePaymentMethod->maskedLabel() }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-gray-500 max-w-[220px] truncate" title="{{ $item->description }}">
                                                {{ $item->description ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 text-right font-semibold tabular-nums">
                                                {{ $item->formattedAmount() }}
                                            </td>
                                            <td class="px-5 py-3">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $itemBadge['bg'] }} {{ $itemBadge['text'] }} {{ $itemBadge['ring'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $itemBadge['dot'] }}"></span>
                                                {{ ucfirst($item->status) }}
                                            </span>
                                            </td>
                                            <td class="px-5 py-3 font-mono text-xs text-gray-400">
                                                {{ $item->stripe_payment_intent_id ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 text-xs text-red-500 max-w-[220px] truncate" title="{{ $item->error_message }}">
                                                {{ $item->error_message ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>

                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="12" class="py-14 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <p class="mt-3 text-gray-500">
                                    No bulk charge batches yet.
                                    <a href="{{ route('admin.stripe.bulk-charge') }}"
                                       class="text-indigo-600 hover:underline ml-1">
                                        Create one
                                    </a>
                                </p>
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>
            </div>

            @if ($batches->hasPages())
                <div class="px-5 py-4 border-t bg-gray-50/70 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700 rounded-b-2xl">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    @endif


    <script>
        function toggleItems(batchId) {
            const row = document.getElementById('items-' + batchId);
            const chevron = document.getElementById('chevron-' + batchId);
            const isHidden = row.classList.contains('hidden');

            row.classList.toggle('hidden', !isHidden);
            chevron.classList.toggle('rotate-90', isHidden);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('chargeItemFilterForm');
            const search = document.getElementById('chargeItemSearch');

            const statusButton = document.getElementById('statusFilterButton');
            const statusDropdown = document.getElementById('statusFilterDropdown');
            const statusSearch = document.getElementById('statusFilterSearch');
            const statusSelectAll = document.getElementById('statusSelectAll');
            const statusClear = document.getElementById('statusClear');
            const statusApply = document.getElementById('statusApply');
            const statusLabel = document.getElementById('statusFilterLabel');

            let searchTimer = null;

            function getStatusCheckboxes() {
                return Array.from(
                    document.querySelectorAll('.status-checkbox')
                );
            }

            function updateStatusLabel() {
                const checkboxes = getStatusCheckboxes();
                const selected = checkboxes.filter(checkbox => checkbox.checked);

                if (!selected.length) {
                    statusLabel.textContent = 'All statuses';
                } else {
                    statusLabel.textContent = `${selected.length} selected`;
                }

                const visibleCheckboxes = checkboxes.filter(checkbox => {
                    const option = checkbox.closest('.status-option');
                    return option && !option.classList.contains('hidden');
                });

                const visibleChecked = visibleCheckboxes.filter(
                    checkbox => checkbox.checked
                );

                statusSelectAll.checked =
                    visibleCheckboxes.length > 0 &&
                    visibleChecked.length === visibleCheckboxes.length;

                statusSelectAll.indeterminate =
                    visibleChecked.length > 0 &&
                    visibleChecked.length < visibleCheckboxes.length;
            }

            // Open/close status dropdown.
            statusButton.addEventListener('click', function (event) {
                event.stopPropagation();

                statusDropdown.classList.toggle('hidden');

                if (!statusDropdown.classList.contains('hidden')) {
                    statusSearch.focus();
                }
            });

            // Don't close when clicking inside.
            statusDropdown.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            // Close when clicking outside.
            document.addEventListener('click', function () {
                statusDropdown.classList.add('hidden');
            });

            // Status search only filters the dropdown options.
            statusSearch.addEventListener('input', function () {
                const value = this.value.trim().toLowerCase();

                document.querySelectorAll('.status-option').forEach(option => {
                    const status = option.dataset.status || '';

                    option.classList.toggle(
                        'hidden',
                        value !== '' && !status.includes(value)
                    );
                });

                updateStatusLabel();
            });

            // Select/deselect visible statuses.
            statusSelectAll.addEventListener('change', function () {
                const checked = this.checked;

                document.querySelectorAll('.status-option').forEach(option => {
                    if (option.classList.contains('hidden')) {
                        return;
                    }

                    const checkbox = option.querySelector('.status-checkbox');

                    if (checkbox) {
                        checkbox.checked = checked;
                    }
                });

                updateStatusLabel();
            });

            // Update count immediately when individual status changes.
            document.querySelectorAll('.status-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateStatusLabel);
            });

            // Clear status selections without submitting immediately.
            statusClear.addEventListener('click', function () {
                document.querySelectorAll('.status-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });

                statusSelectAll.checked = false;
                statusSelectAll.indeterminate = false;

                updateStatusLabel();
            });

            // Apply status filter.
            statusApply.addEventListener('click', function () {
                statusDropdown.classList.add('hidden');
                form.submit();
            });

            // Search with debounce.
            search.addEventListener('input', function () {
                clearTimeout(searchTimer);

                searchTimer = setTimeout(function () {
                    form.submit();
                }, 400);
            });

            updateStatusLabel();
        });
    </script>

</x-app-layout>

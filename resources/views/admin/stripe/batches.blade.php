<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Bulk Charge Batches
            </h2>
            <a href="{{ route('admin.stripe.bulk-charge') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                + New Bulk Charge
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                View
            </h3>

            <div
                class="mt-2 inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1">
                <a href="{{ route('admin.stripe.batches.index', ['view' => 'batches']) }}"
                   class="px-4 py-2 text-sm font-medium rounded-md transition
                    {{ $view === 'batches'
                        ? 'bg-indigo-600 text-white'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    Batches
                </a>

                <a href="{{ route('admin.stripe.batches.index', ['view' => 'items']) }}"
                   class="px-4 py-2 text-sm font-medium rounded-md transition
                    {{ $view === 'items'
                        ? 'bg-indigo-600 text-white'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    Individual Charges
                </a>
            </div>
        </div>
    </div>

    @if ($view === 'items')

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-visible">

    {{-- FILTER BAR --}}
    <div class="relative z-50 p-4 border-b border-gray-200 dark:border-gray-700 overflow-visible">

        <form
            id="chargeItemFilterForm"
            method="GET"
            action="{{ route('admin.stripe.batches.index') }}"
            class="flex flex-wrap items-center gap-3"
        >
            <input type="hidden" name="view" value="items">

            {{-- SEARCH --}}
            <div class="flex-1 min-w-[280px]">
                <input
                    id="chargeItemSearch"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search customer, email, description, payment intent..."
                    autocomplete="off"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600
                           dark:bg-gray-700 dark:text-white
                           focus:border-indigo-500 focus:ring-indigo-500"
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
                           rounded-lg border border-gray-300
                           dark:border-gray-600
                           bg-white dark:bg-gray-700
                           text-sm text-gray-700 dark:text-gray-200
                           hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <span id="statusFilterLabel">
                        @if(count($selectedStatuses))
                            {{ count($selectedStatuses) }} selected
                        @else
                            All statuses
                        @endif
                    </span>

                    <svg
                        class="w-4 h-4"
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
                           rounded-lg shadow-xl"
                >

                    {{-- STATUS SEARCH --}}
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                        <input
                            type="text"
                            id="statusFilterSearch"
                            placeholder="Search status..."
                            autocomplete="off"
                            class="w-full rounded-md
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
                                   rounded cursor-pointer
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
                                       px-2 py-2 rounded cursor-pointer
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
                            class="px-3 py-1.5 text-xs rounded-md
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
                            class="px-3 py-1.5 text-xs rounded-md
                                   bg-indigo-600 text-white
                                   hover:bg-indigo-700"
                        >
                            Apply
                        </button>

                    </div>

                </div>
            </div>

            {{-- CLEAR ALL FILTERS --}}
            @if(request()->hasAny(['search', 'status']))
                <a
                    href="{{ route('admin.stripe.batches.index', ['view' => 'items']) }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700
                           rounded-lg hover:bg-gray-200
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

            <thead class="bg-gray-100 dark:bg-gray-700">

            <tr>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Customer
                </th>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Batch
                </th>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Payment Method
                </th>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Description
                </th>

                <th class="px-5 py-3 text-right text-xs
                           font-semibold text-gray-500 uppercase">
                    Amount
                </th>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Status
                </th>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Payment Intent
                </th>

                <th class="px-5 py-3 text-left text-xs
                           font-semibold text-gray-500 uppercase">
                    Error
                </th>

            </tr>

            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

            @forelse($items as $item)

                @php
                    $itemBadge = match($item->status) {
                        'succeeded' => [
                            'bg' => 'bg-green-100',
                            'text' => 'text-green-700'
                        ],
                        'processing' => [
                            'bg' => 'bg-blue-100',
                            'text' => 'text-blue-700'
                        ],
                        'failed' => [
                            'bg' => 'bg-red-100',
                            'text' => 'text-red-700'
                        ],
                        default => [
                            'bg' => 'bg-gray-100',
                            'text' => 'text-gray-500'
                        ],
                    };
                @endphp

                <tr class="hover:bg-gray-50
                           dark:hover:bg-gray-700 transition">

                    <td class="px-5 py-4">

                        <div class="font-medium">
                            {{ $item->stripeCustomer->name
                                ?? $item->stripeCustomer->email
                                ?? '-' }}
                        </div>

                        @if(
                            $item->stripeCustomer?->email &&
                            $item->stripeCustomer?->name
                        )
                            <div class="text-xs text-gray-400">
                                {{ $item->stripeCustomer->email }}
                            </div>
                        @endif

                    </td>

                    <td class="px-5 py-4">

                        <a
                            href="{{ route(
                                'admin.stripe.batches.show',
                                $item->batch
                            ) }}"
                            class="font-mono text-sm
                                   text-indigo-600 hover:underline"
                        >
                            {{ $item->batch->reference }}
                        </a>

                    </td>

                    <td class="px-5 py-4 font-mono text-xs text-gray-500">
                        {{ $item->stripePaymentMethod->maskedLabel() }}
                    </td>

                    <td class="px-5 py-4 text-gray-500">
                        {{ $item->description ?? '-' }}
                    </td>

                    <td class="px-5 py-4 text-right font-semibold">
                        {{ $item->formattedAmount() }}
                    </td>

                    <td class="px-5 py-4">

                        <span
                            class="px-2 py-0.5 rounded-full text-xs
                                   font-semibold
                                   {{ $itemBadge['bg'] }}
                                   {{ $itemBadge['text'] }}"
                        >
                            {{ ucfirst($item->status) }}
                        </span>

                    </td>

                    <td class="px-5 py-4 font-mono text-xs text-gray-400">
                        {{ $item->stripe_payment_intent_id ?? '-' }}
                    </td>

                    <td class="px-5 py-4 text-xs text-red-500 max-w-xs">
                        {{ $item->error_message ?? '-' }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="8"
                        class="py-10 text-center text-gray-500"
                    >
                        No charges found.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    {{-- PAGINATION --}}
    @if ($items->hasPages())

        <div
            class="px-5 py-4 border-t
                   border-gray-200 dark:border-gray-700"
        >
            {{ $items->links() }}
        </div>

    @endif

</div>


@else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-3 w-8"></th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customers</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Completed</th>
                        <th></th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                    @forelse ($batches as $batch)

                        @php
                            $badge = match($batch->status) {
                                'completed'             => ['bg' => 'bg-green-100',  'text' => 'text-green-700',  'label' => 'Completed'],
                                'processing'            => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'label' => 'Processing'],
                                'completed_with_errors' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => 'Completed with Errors'],
                                'failed'                => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'label' => 'Failed'],
                                default                 => ['bg' => 'bg-gray-100',   'text' => 'text-gray-700',   'label' => ucfirst($batch->status)],
                            };
                        @endphp

                        {{-- Batch row --}}
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                            onclick="toggleItems({{ $batch->id }})">

                            {{-- EXPAND TOGGLE --}}
                            <td class="px-5 py-4 text-gray-400">
                                <svg id="chevron-{{ $batch->id }}"
                                     class="w-4 h-4 transition-transform duration-200"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5l7 7-7 7"/>
                                </svg>
                            </td>

                            {{-- REFERENCE --}}
                            <td class="px-5 py-4">
                                <div class="font-mono font-semibold text-sm">{{ $batch->reference }}</div>
                                <div class="text-xs text-gray-400">{{ strtoupper($batch->currency) }}</div>
                            </td>

                            {{-- CREATED --}}
                            <td class="px-5 py-4">
                                <div class="font-medium">{{ $batch->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $batch->created_at->format('g:i A') }}</div>
                            </td>

                            {{-- CUSTOMERS --}}
                            <td class="px-5 py-4">
                                <div class="font-medium">{{ number_format($batch->customer_count) }}</div>
                            </td>

                            {{-- TOTAL --}}
                            <td class="px-5 py-4">
                                <div class="font-bold text-lg">{{ $batch->formattedTotal() }}</div>
                            </td>

                            {{-- STATUS --}}
                            <td class="px-5 py-4">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold {{ $badge['bg'] }} {{ $badge['text'] }}">
                                {{ $badge['label'] }}
                            </span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-mono font-semibold text-sm">{{ $batch->createdBy->name ?? '' }}</div>
                            </td>

                            {{-- COMPLETED --}}
                            <td class="px-5 py-4">
                                @if ($batch->completed_at)
                                    <div class="text-sm">{{ $batch->completed_at->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $batch->completed_at->format('g:i A') }}</div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>

                            {{-- ACTION --}}
                            <td class="px-5 py-4 text-right" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.stripe.batches.show', $batch) }}"
                                   class="inline-flex items-center px-3 py-2 text-sm bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100">
                                    View
                                </a>
                            </td>

                        </tr>

                        {{-- Line items row (hidden by default) --}}
                        <tr id="items-{{ $batch->id }}" class="hidden">
                            <td colspan="9" class="px-0 py-0 bg-gray-50 dark:bg-gray-900/40">

                                <table class="min-w-full text-sm">
                                    <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="pl-16 pr-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">
                                            Customer
                                        </th>
                                        <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">
                                            Payment Method
                                        </th>
                                        <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">
                                            Description
                                        </th>
                                        <th class="px-5 py-2 text-right text-xs font-semibold text-gray-400 uppercase">
                                            Amount
                                        </th>
                                        <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">
                                            Status
                                        </th>
                                        <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">
                                            Payment Intent
                                        </th>
                                        <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">
                                            Error
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($batch->items as $item)
                                        @php
                                            $itemBadge = match($item->status) {
                                                'succeeded'  => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
                                                'processing' => ['bg' => 'bg-blue-100',  'text' => 'text-blue-700'],
                                                'failed'     => ['bg' => 'bg-red-100',   'text' => 'text-red-700'],
                                                default      => ['bg' => 'bg-gray-100',  'text' => 'text-gray-500'],
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                            <td class="pl-16 pr-5 py-3 font-medium">
                                                {{ $item->stripeCustomer->name ?? $item->stripeCustomer->email ?? '-' }}
                                                @if ($item->stripeCustomer->email && $item->stripeCustomer->name)
                                                    <div
                                                        class="text-xs text-gray-400">{{ $item->stripeCustomer->email }}</div>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 font-mono text-xs text-gray-500">
                                                {{ $item->stripePaymentMethod->maskedLabel() }}
                                            </td>
                                            <td class="px-5 py-3 text-gray-500">
                                                {{ $item->description ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 text-right font-semibold">
                                                {{ $item->formattedAmount() }}
                                            </td>
                                            <td class="px-5 py-3">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $itemBadge['bg'] }} {{ $itemBadge['text'] }}">
                                                {{ ucfirst($item->status) }}
                                            </span>
                                            </td>
                                            <td class="px-5 py-3 font-mono text-xs text-gray-400">
                                                {{ $item->stripe_payment_intent_id ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 text-xs text-red-500 max-w-xs">
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
                            <td colspan="8" class="py-10 text-center text-gray-500">
                                No bulk charge batches yet.
                                <a href="{{ route('admin.stripe.bulk-charge') }}"
                                   class="text-indigo-600 hover:underline ml-1">
                                    Create one
                                </a>
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>
            </div>

            @if ($batches->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
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

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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
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
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badge['bg'] }} {{ $badge['text'] }}">
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
                                    <th class="pl-16 pr-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">Customer</th>
                                    <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">Payment Method</th>
                                    <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">Description</th>
                                    <th class="px-5 py-2 text-right text-xs font-semibold text-gray-400 uppercase">Amount</th>
                                    <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                                    <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">Payment Intent</th>
                                    <th class="px-5 py-2 text-left text-xs font-semibold text-gray-400 uppercase">Error</th>
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
                                                <div class="text-xs text-gray-400">{{ $item->stripeCustomer->email }}</div>
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
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $itemBadge['bg'] }} {{ $itemBadge['text'] }}">
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
                            <a href="{{ route('admin.stripe.bulk-charge') }}" class="text-indigo-600 hover:underline ml-1">
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

    <script>
        function toggleItems(batchId) {
            const row     = document.getElementById('items-' + batchId);
            const chevron = document.getElementById('chevron-' + batchId);
            const isHidden = row.classList.contains('hidden');

            row.classList.toggle('hidden', !isHidden);
            chevron.classList.toggle('rotate-90', isHidden);
        }
    </script>

</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Batch {{ $batch->reference }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">

        @if (session('success'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Batch summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-gray-500">Reference</div>
                <div class="font-mono font-semibold">{{ $batch->reference }}</div>
            </div>
            <div>
                <div class="text-gray-500">Stripe Account</div>
                <div class="font-semibold">{{ $batch->stripeAccount?->display_name ?? 'Legacy pool' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Customers</div>
                <div class="font-semibold">{{ $batch->customer_count }}</div>
            </div>
            <div>
                <div class="text-gray-500">Total</div>
                <div class="font-semibold">{{ $batch->formattedTotal() }}</div>
            </div>
            <div>
                <div class="text-gray-500">Status</div>
                @php
                    $badge = match($batch->status) {
                        'completed'             => 'bg-green-100 text-green-700',
                        'processing'            => 'bg-blue-100 text-blue-700',
                        'completed_with_errors' => 'bg-yellow-100 text-yellow-700',
                        'failed'                => 'bg-red-100 text-red-700',
                        default                 => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                    {{ ucwords(str_replace('_', ' ', $batch->status)) }}
                </span>
            </div>
        </div>

        {{-- Items table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment Method</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PaymentIntent</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Error</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($batch->items as $item)
                        @php
                            $itemBadge = match($item->status) {
                                'succeeded'  => 'bg-green-100 text-green-700',
                                'processing' => 'bg-blue-100 text-blue-700',
                                'failed'     => 'bg-red-100 text-red-700',
                                default      => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-5 py-4 font-medium">{{ $item->stripeCustomer->name ?? $item->stripeCustomer->email ?? '-' }}</td>
                            <td class="px-5 py-4 font-mono text-sm">{{ $item->stripePaymentMethod->maskedLabel() }}</td>
                            <td class="px-5 py-4 text-right">{{ $item->formattedAmount() }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $item->description ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $itemBadge }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-gray-500">
                                {{ $item->stripe_payment_intent_id ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-xs text-red-600 max-w-xs">
                                {{ $item->error_message ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-sm whitespace-nowrap">
                                @if($item->status === 'failed' && ! $item->stripe_payment_intent_id)
                                    <form method="POST" action="{{ route('admin.stripe.items.retry', $item) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition">
                                            Retry
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>
        </div>

    </div>

</x-app-layout>

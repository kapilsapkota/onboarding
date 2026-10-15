<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            {{ $customer->name ?? $customer->email ?? $customer->stripe_customer_id }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">

        <a href="{{ route('admin.stripe.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to customers</a>

        {{-- Summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-gray-500">Name</div>
                <div class="font-semibold">{{ $customer->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Email</div>
                <div class="font-semibold">{{ $customer->email ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Stripe ID</div>
                <div class="font-mono font-semibold text-xs">{{ $customer->stripe_customer_id }}</div>
            </div>
            <div>
                <div class="text-gray-500">Stripe Account</div>
                <div class="font-semibold">{{ $customer->stripeAccount?->display_name ?? 'Legacy pool' }}</div>
                @if($customer->stripeAccount?->company)
                    <div class="text-xs text-gray-400">{{ $customer->stripeAccount->company->name }}</div>
                @endif
            </div>
            <div>
                <div class="text-gray-500">Our Customer</div>
                @if($customer->clients->isNotEmpty())
                    @foreach($customer->clients as $client)
                        <div class="font-semibold">{{ $client->company_name }}</div>
                        @if($client->company)
                            <div class="text-xs text-gray-400">{{ $client->company->name }}</div>
                        @endif
                    @endforeach
                @else
                    <div class="text-gray-400">Not linked</div>
                @endif
            </div>
            <div>
                <div class="text-gray-500">Last Synced</div>
                <div class="font-semibold">{{ $customer->last_synced_at?->format('d M Y g:i A') ?? '-' }}</div>
            </div>
        </div>

        {{-- Payment methods --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 font-semibold">BECS Payment Methods ({{ $customer->paymentMethods->count() }})</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stripe PM ID</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Account Holder</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Default</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($customer->paymentMethods as $pm)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-5 py-4 font-mono text-sm">{{ $pm->maskedLabel() }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $pm->stripe_payment_method_id }}</td>
                                <td class="px-5 py-4 text-sm">{{ $pm->account_holder_name ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $pm->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($pm->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm">{{ $pm->is_default ? 'Yes' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">No payment methods on file.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent charges --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 font-semibold">Recent Charges</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Batch</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PaymentIntent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($customer->batchItems as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.stripe.batches.show', $item->batch) }}" class="font-mono text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ $item->batch->reference }}
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-right">{{ $item->formattedAmount() }}</td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ ucfirst($item->status) }}</span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $item->stripe_payment_intent_id ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No charges yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>

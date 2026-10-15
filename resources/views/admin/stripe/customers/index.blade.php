<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Stripe Customers
            </h2>
            <form method="POST" action="{{ route('admin.stripe.customers.sync') }}" class="inline">
                @csrf
                @if($selectedAccount instanceof \App\Models\StripeAccount)
                    <input type="hidden" name="stripe_account_id" value="{{ $selectedAccount->id }}">
                @endif
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow-sm hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0112-3.5M19 15a7 7 0 01-12 3.5"/>
                    </svg>
                    Sync from Stripe{{ $selectedAccount instanceof \App\Models\StripeAccount ? ' ('.$selectedAccount->display_name.')' : '' }}
                </button>
            </form>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- FILTER BAR --}}
        <div class="p-4 bg-gray-50/70 dark:bg-gray-900/40 border-b border-gray-200 dark:border-gray-700 rounded-t-2xl">
            <form method="GET" action="{{ route('admin.stripe.customers.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[240px] relative">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search name, email or cus_ id..."
                           autocomplete="off"
                           class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm pl-10 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>
                <div>
                    <select name="stripe_account" onchange="this.form.submit()"
                            class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" @selected($selectedAccount === 'all')>All accounts</option>
                        <option value="unassigned" @selected($selectedAccount === 'unassigned')>Unassigned (legacy)</option>
                        @foreach($stripeAccounts as $stripeAccount)
                            <option value="{{ $stripeAccount->id }}" @selected($selectedAccount instanceof \App\Models\StripeAccount && $selectedAccount->id === $stripeAccount->id)>
                                {{ $stripeAccount->display_name }}{{ $stripeAccount->company ? ' — '.$stripeAccount->company->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm font-medium rounded-xl hover:bg-gray-700 transition">Filter</button>
                @if(request()->hasAny(['search', 'stripe_account']))
                    <a href="{{ route('admin.stripe.customers.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/60">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Stripe ID</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Account</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">BECS Methods</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Our Customer</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Last Synced</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                    @forelse($customers as $customer)
                        @php
                            $initials = strtoupper(collect(preg_split('/\s+/', trim($customer->name ?? $customer->email ?? '?')))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
                        @endphp
                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-gray-700/50 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="hidden sm:inline-flex w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold items-center justify-center shrink-0">
                                        {{ $initials }}
                                    </span>
                                    <span>
                                        <span class="block font-medium text-gray-900 dark:text-gray-100">
                                            {{ $customer->name ?? $customer->email ?? '-' }}
                                        </span>
                                        @if($customer->name && $customer->email)
                                            <span class="block text-xs text-gray-400">{{ $customer->email }}</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $customer->stripe_customer_id }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 font-mono text-xs text-gray-600 dark:text-gray-300">
                                    {{ $customer->stripeAccount?->display_name ?? 'Legacy pool' }}
                                </span>
                                @if($customer->stripeAccount?->company)
                                    <div class="mt-1 text-[11px] text-gray-400">{{ $customer->stripeAccount->company->name }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($customer->active_becs_count > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        {{ $customer->active_becs_count }} active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        No active BECS
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm">
                                @if($customer->clients->isNotEmpty())
                                    @foreach($customer->clients as $client)
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $client->company_name }}</div>
                                        @if($client->company)
                                            <div class="text-xs text-gray-400">{{ $client->company->name }}</div>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-400 whitespace-nowrap">
                                {{ $customer->last_synced_at?->format('d M Y g:i A') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.stripe.customers.show', $customer) }}"
                                   class="inline-flex items-center px-3 py-2 text-sm font-medium bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-100 transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="mt-3 text-gray-500">No Stripe customers found. Try syncing from Stripe.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="px-5 py-4 border-t bg-gray-50/70 dark:bg-gray-900/40 border-gray-200 dark:border-gray-700 rounded-b-2xl">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</x-app-layout>

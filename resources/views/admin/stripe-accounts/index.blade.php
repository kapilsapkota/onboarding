<x-app-layout>
    @section('title', 'Stripe Accounts')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        Stripe Accounts
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        One or more Stripe accounts per company, each with its own keys and DDR form
                    </p>
                </div>
            </div>

            @can('create-stripe-account')
                <button onclick="openCreateModal()"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Account
                </button>
            @endcan
        </div>
    </x-slot>

    <x-message/>

    <div class="space-y-5 pb-10">
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Accounts</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($accounts->count()) }}</p>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Active</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($accounts->where('status', 'active')->count()) }}</p>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Linked Customers</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($accounts->sum('stripe_customers_count')) }}</p>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Batches</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($accounts->sum('charge_batches_count')) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    <div class="overflow-x-auto">
                        <table class="min-w-full">

                    {{-- Table Header --}}
                    <thead class="border-b border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/40">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="whitespace-nowrap px-5 py-3.5">Account</th>
                        <th class="whitespace-nowrap px-5 py-3.5">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5">Keys</th>
                        <th class="min-w-[340px] px-5 py-3.5">Links</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right">Actions</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                    @forelse($accounts as $account)

                        @php
                            $ddrUrl = $account->ddrUrl();
                            $webhookUrl = url('/webhooks/stripe/'.$account->id);
                        @endphp

                        <tr class="group transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-700/30">

                            {{-- Account --}}
                            <td class="px-5 py-4">
                                <div class="min-w-[180px]">

                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $account->display_name }}
                                        </span>

                                        @if($account->is_default)
                                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                Default
                                            </span>
                                        @endif

                                        @if($account->is_legacy)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                Legacy
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                                        <span>{{ $account->company?->name ?? 'No company' }}</span>
                                        <span>·</span>
                                        <span>#{{ $account->id }}</span>
                                    </div>

                                </div>
                            </td>


                            {{-- Status --}}
                            <td class="px-5 py-4">
                                @if($account->status === 'active')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        Disabled
                                    </span>

                                @endif
                            </td>


                            {{-- Keys --}}
                            <td class="px-5 py-4">
                                <div class="min-w-[150px]">

                                    <div class="flex flex-wrap items-center gap-1.5">

                                        <span
                                            title="Publishable key"
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 font-mono text-[10px] font-medium {{ $account->publishable_key ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}"
                                        >
                                            <span>pk</span>
                                            <span>{{ $account->publishable_key ? '✓' : '—' }}</span>
                                        </span>

                                        <span
                                            title="Secret key"
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 font-mono text-[10px] font-medium {{ $account->secret_key ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}"
                                        >
                                            <span>sk</span>
                                            <span>{{ $account->secret_key ? '✓' : '—' }}</span>
                                        </span>

                                        <span
                                            title="Webhook secret"
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 font-mono text-[10px] font-medium {{ $account->webhook_secret ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}"
                                        >
                                            <span>wh</span>
                                            <span>{{ $account->webhook_secret ? '✓' : '—' }}</span>
                                        </span>

                                    </div>

                                    <div class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ $account->stripe_customers_count }} customers
                                        <span class="mx-1">·</span>
                                        {{ $account->charge_batches_count }} batches
                                    </div>

                                </div>
                            </td>


                            {{-- Links --}}
                            <td class="px-5 py-4">
                                <div class="w-full max-w-[520px] space-y-2.5">

                                    {{-- DDR --}}
                                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800">

                                        {{-- Icon --}}
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="1.8"
                                                      d="M13.5 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9.5L13.5 3z"/>
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="1.8"
                                                      d="M13 3v7h7"/>
                                            </svg>
                                        </div>

                                        {{-- Details --}}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-100">
                                                    DDR Page
                                                </span>

                                                <span class="rounded-full bg-blue-50 px-1.5 py-0.5 text-[9px] font-medium text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                                    Customer
                                                </span>
                                            </div>

                                            <a
                                                href="{{ $ddrUrl }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="mt-0.5 block truncate font-mono text-[10px] text-gray-400 transition hover:text-blue-600 hover:underline dark:text-gray-500 dark:hover:text-blue-400"
                                            >
                                                {{ $ddrUrl }}
                                            </a>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex shrink-0 items-center gap-1">

                                            <a
                                                href="{{ $ddrUrl }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="Open DDR page"
                                                class="inline-flex h-8 items-center gap-1.5 rounded-md bg-blue-600 px-2.5 text-xs font-medium text-white transition hover:bg-blue-700"
                                            >
                                                Open

                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>

                                            <button
                                                type="button"
                                                onclick="copyText(@js($ddrUrl), this)"
                                                title="Copy DDR link"
                                                class="copy-btn inline-flex h-8 items-center gap-1.5 rounded-md border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                            >
                                                <svg class="h-3.5 w-3.5 copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 002 2v8a2 2 0 002 2z"/>
                                                </svg>

                                                <span>Copy</span>
                                            </button>

                                        </div>
                                    </div>


                                    {{-- Stripe Webhook --}}
                                    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

                                        <div class="flex items-center gap-3 px-3 py-2.5">

                                            {{-- Icon --}}
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.8"
                                                          d="M12 3v18M3 12h18"/>
                                                </svg>
                                            </div>

                                            {{-- Details --}}
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-100">
                                                        Stripe Webhook
                                                    </span>

                                                    <span class="rounded-full bg-purple-50 px-1.5 py-0.5 text-[9px] font-medium text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                                                        Endpoint
                                                    </span>
                                                </div>

                                                <p class="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500">
                                                    Configure this endpoint in Stripe
                                                </p>
                                            </div>

                                            {{-- Toggle --}}
                                            <button
                                                type="button"
                                                onclick="toggleWebhookUrl(this)"
                                                class="webhook-toggle inline-flex h-8 shrink-0 items-center gap-1.5 rounded-md border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.8"
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.8"
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>

                                                <span>Show URL</span>
                                            </button>
                                        </div>


                                        {{-- Hidden Webhook URL --}}
                                        <div class="webhook-url hidden border-t border-gray-100 px-3 py-2 dark:border-gray-700">

                                            <div class="flex items-center gap-2 rounded-md bg-gray-50 p-2 dark:bg-gray-900">

                                                <code class="min-w-0 flex-1 break-all font-mono text-[10px] leading-relaxed text-gray-500 dark:text-gray-400">
                                                    {{ $webhookUrl }}
                                                </code>

                                                <button
                                                    type="button"
                                                    onclick="copyText(@js($webhookUrl), this)"
                                                    class="copy-btn inline-flex h-7 shrink-0 items-center gap-1 rounded-md border border-gray-200 bg-white px-2 text-[10px] font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                                >
                                                    <svg class="h-3 w-3 copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 002 2v8a2 2 0 002 2z"/>
                                                    </svg>

                                                    <span>Copy</span>
                                                </button>

                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </td>


                            {{-- Actions --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">

                                    @can('edit-stripe-account')

                                        @php
                                            $editPayload = [
                                                'id' => $account->id,
                                                'display_name' => $account->display_name,
                                                'company_id' => $account->company_id,
                                                'publishable_key' => $account->publishable_key,
                                                'is_default' => $account->is_default,
                                                'status' => $account->status,
                                            ];
                                        @endphp

                                        <button
                                            type="button"
                                            onclick='openEditModal(@json($editPayload))'
                                            title="Edit account"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>

                                            Edit
                                        </button>

                                    @endcan


                                    @can('delete-stripe-account')

                                        <x-delete-modal-button
                                            :model="$account"
                                            :action="route('admin.stripe-accounts.destroy', $account)"
                                            title="Delete Stripe Account"
                                            :display_name="$account->display_name"
                                        >
                                            Delete
                                        </x-delete-modal-button>

                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="1.8"
                                              d="M12 9v3m0 4h.01M4.93 19h14.14a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.2 16a2 2 0 001.73 3z"/>
                                    </svg>
                                </div>

                                <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    No Stripe accounts yet
                                </h3>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Run the legacy seeder or add your first Stripe account.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                    </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </div>

    {{-- Account modal --}}
    <div x-data="stripeAccountModal()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px]" @click="open = false"></div>

        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="edit ? 'Edit Stripe account' : 'Add Stripe account'"></h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Secrets are write-only and never shown
                        back.</p>
                </div>
                <button type="button" @click="open = false"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="edit ? '/admin/stripe-accounts/' + account.id : '/admin/stripe-accounts'" method="POST">
                @csrf
                <template x-if="edit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="is_default" value="0">

                <div class="max-h-[65vh] space-y-4 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Display
                                name</label>
                            <input type="text" name="display_name" x-model="account.display_name"
                                   placeholder="e.g. Primary AUD" required
                                   class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Company</label>
                            <select name="company_id" x-model="account.company_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— No company —</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Publishable
                            key</label>
                        <input type="text" name="publishable_key" x-model="account.publishable_key"
                               placeholder="pk_live_..." autocomplete="off"
                               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Secret key <span
                                class="font-normal text-gray-400" x-text="edit ? '(blank = keep current)' : ''"></span></label>
                        <input type="password" name="secret_key" placeholder="sk_live_... / rk_live_..."
                               autocomplete="new-password"
                               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Webhook secret
                            <span class="font-normal text-gray-400"
                                  x-text="edit ? '(blank = keep current)' : ''"></span></label>
                        <input type="password" name="webhook_secret" placeholder="whsec_..." autocomplete="new-password"
                               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label
                            class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm dark:border-gray-600">
                            <input type="checkbox" name="is_default" value="1" :checked="!!account.is_default"
                                   class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span class="text-gray-700 dark:text-gray-200">Default for company</span>
                        </label>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select name="status" x-model="account.status"
                                    class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <button type="button" @click="open = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button
                        class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Save account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function stripeAccountModal() {
            return {
                open: false,
                edit: false,
                account: {},

                init() {
                    window.openCreateModal = () => {
                        this.edit = false;
                        this.account = {status: 'active', company_id: ''};
                        this.open = true;
                    };

                    window.openEditModal = (account) => {
                        this.edit = true;
                        this.account = {
                            id: account.id,
                            display_name: account.display_name,
                            company_id: account.company_id ? String(account.company_id) : '',
                            publishable_key: account.publishable_key || '',
                            is_default: !!account.is_default,
                            status: account.status,
                        };
                        this.open = true;
                    };
                }
            }
        }
        /**
         * Copy a URL and temporarily change the button to "Copied!"
         */
        function setCopyState(button, text) {
            const label = button.querySelector('span');
            if (label) {
                label.textContent = text;
            } else {
                button.textContent = text;
            }
        }

        function resetCopyState(button, originalText, stateClass) {
            setCopyState(button, originalText);
            button.classList.remove(stateClass);
            const icon = button.querySelector('.copy-icon');
            if (icon) {
                icon.classList.remove(stateClass);
            }
            if (stateClass === 'text-emerald-600') {
                button.classList.add('text-gray-600');
            }
        }

        function copyText(text, button) {
            const label = button.querySelector('span');
            const icon = button.querySelector('.copy-icon');
            const originalText = label ? label.textContent : button.textContent.trim();

            navigator.clipboard.writeText(text).then(() => {
                setCopyState(button, 'Copied!');
                button.classList.remove('text-gray-600', 'text-gray-400', 'text-gray-300');
                button.classList.add('text-emerald-600');
                if (icon) {
                    icon.classList.add('text-emerald-600');
                }
                setTimeout(() => resetCopyState(button, originalText, 'text-emerald-600'), 2000);
            }).catch(() => {
                setCopyState(button, 'Failed');
                button.classList.add('text-red-600');
                setTimeout(() => resetCopyState(button, originalText, 'text-red-600'), 2000);
            });
        }


        /**
         * Toggle Stripe webhook URL visibility
         */
        function toggleWebhookUrl(button) {
            const wrapper = button.closest('.rounded-lg');
            const urlContainer = wrapper.querySelector('.webhook-url');
            const label = button.querySelector('span');

            if (!urlContainer) {
                return;
            }

            const isHidden = urlContainer.classList.toggle('hidden');

            label.textContent = isHidden ? 'Show URL' : 'Hide URL';
        }
    </script>

</x-app-layout>

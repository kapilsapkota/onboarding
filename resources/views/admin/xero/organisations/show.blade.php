<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $organisation->tenant_name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Organisation hub · {{ $organisation->is_active ? 'Active' : 'Inactive' }}"
                :title="$organisation->tenant_name"
                :subtitle="'Xero ID ' . $organisation->tenant_id . ($organisation->tenant_type ? ' · ' . $organisation->tenant_type : '')">
                <x-slot name="actions">
                    <a href="{{ route('admin.xero.organisations.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        All organisations
                    </a>
                    @can('configure-xero')
                        <a href="{{ route('admin.xero.organisations.edit', $organisation) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            Edit links
                        </a>
                    @endcan
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Contacts" :value="number_format($contactStats['total'])" :sub="$contactStats['matched'] . ' matched · ' . $contactStats['unmatched'] . ' unmatched'" accent="sky"
                    :href="route('admin.xero.reconcile.index', ['tenant' => $organisation->id])" />
                <x-ui.stat label="Invoices" :value="number_format($invoiceStats['total'])" accent="orange"
                    :href="route('admin.xero.invoices.index', ['tenant' => $organisation->id])" />
                <x-ui.stat label="Outstanding" :value="'$' . number_format($invoiceStats['outstanding'], 2)" :sub="$invoiceStats['overdue'] . ' overdue'" accent="rose" />
                <x-ui.stat label="Direct debit account" :value="$organisation->dd_bank_account_name ?? 'Not set'" accent="emerald"
                    :href="route('admin.xero.tenants.bank-settings', $organisation)" />
            </div>

            {{-- SOURCE OF TRUTH LINKS --}}
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card title="Company link" subtitle="The business this organisation bills as.">
                    <x-slot name="actions">
                        @can('configure-xero')
                            <a href="{{ route('admin.xero.organisations.edit', $organisation) }}" class="text-xs font-bold text-[#F06927] hover:text-[#D14F1A]">Change →</a>
                        @endcan
                    </x-slot>
                    <div class="px-6 py-5">
                        @if($organisation->company)
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F06927] text-lg font-extrabold text-white shadow-md">
                                    {{ strtoupper(substr($organisation->company->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $organisation->company->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $organisation->company->clients_count ?? $organisation->company->clients()->count() }} clients · {{ $organisation->company->stripeAccounts->count() }} Stripe accounts</div>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2">
                                @foreach($organisation->company->stripeAccounts as $account)
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-gray-700/40">
                                        <div class="min-w-0">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $account->display_name }}</span>
                                            @if($organisation->default_stripe_account_id === $account->id)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-[#F06927]/10 px-2 py-0.5 text-[10px] font-bold text-[#F06927]">Default for this org</span>
                                            @endif
                                            <div class="truncate font-mono text-[11px] text-gray-400">{{ $account->ddrUrl() }}</div>
                                        </div>
                                        <a href="{{ route('admin.stripe-accounts.index') }}" class="shrink-0 text-xs font-bold text-[#F06927] hover:text-[#D14F1A]">Manage →</a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Not attached yet. Link a company so Stripe accounts, clients and direct-debit runs resolve against this organisation.</p>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card title="Default Stripe account" subtitle="Used for collections against this organisation.">
                    <div class="px-6 py-5">
                        @if($organisation->defaultStripeAccount)
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-lg font-extrabold text-white shadow-md">
                                    {{ strtoupper(substr($organisation->defaultStripeAccount->display_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $organisation->defaultStripeAccount->display_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $organisation->defaultStripeAccount->company?->name ?? 'No company' }} · {{ $organisation->defaultStripeAccount->status }}</div>
                                </div>
                            </div>
                            <div class="mt-3 font-mono text-[11px] text-gray-400">Webhook: {{ url('/webhooks/stripe/' . $organisation->defaultStripeAccount->id) }}</div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No default account. Collections fall back to the company's account picker.</p>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            {{-- STRIPE CUSTOMERS UNDER THIS ORG --}}
            <x-ui.card title="Stripe customers" subtitle="Customers reachable from this organisation, with their client and Xero-contact match state.">
                <x-slot name="actions">
                    <a href="{{ route('admin.xero.reconcile.index', ['tenant' => $organisation->id]) }}" class="text-xs font-bold text-[#F06927] hover:text-[#D14F1A]">Reconcile →</a>
                </x-slot>
                @if($stripeCustomers->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No Stripe customers yet. Link a company and Stripe account first.</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($stripeCustomers as $row)
                            @php
                                $client = $row['client'];
                                $xeroMatch = $client?->xeroContacts->firstWhere('xero_tenant_id', $organisation->id);
                            @endphp
                            <li class="flex flex-col gap-2 px-6 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center dark:hover:bg-gray-700/40">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $row['customer']->name ?? $row['customer']->stripe_customer_id }}</div>
                                    <div class="truncate text-xs text-gray-400">{{ $row['customer']->email ?? 'No email' }} · {{ $row['customer']->stripeAccount->display_name }} · <span class="font-mono">{{ $row['customer']->stripe_customer_id }}</span></div>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    @if($client)
                                        <a href="{{ route('clients.show', $client) }}" class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold text-sky-700 hover:bg-sky-200 dark:bg-sky-900/40 dark:text-sky-300">{{ $client->company_name }}</a>
                                        @if($xeroMatch)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Xero linked</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">No Xero link</span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-400">No client</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- SYNC STATUS --}}
            <x-ui.card title="Sync status" subtitle="Last successful pulls from Xero.">
                <x-slot name="actions">
                    <form method="POST" action="{{ route('admin.xero.tenants.sync', $organisation) }}">
                        @csrf
                        <select name="type" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option selected disabled>Sync…</option>
                            <option value="contacts">Contacts</option>
                            <option value="invoices">Invoices</option>
                            <option value="repeating">Repeating invoices</option>
                            <option value="all">Full sync</option>
                            <option value="full">Full resync</option>
                        </select>
                    </form>
                </x-slot>
                <div class="grid grid-cols-2 gap-4 px-6 py-5 md:grid-cols-4">
                    @foreach(['Contacts' => $organisation->last_contact_synced_at, 'Invoices' => $organisation->last_invoice_synced_at, 'Payments' => $organisation->last_payment_synced_at, 'Repeating' => $organisation->last_repeating_invoice_synced_at] as $label => $at)
                        <div>
                            <div class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">{{ $label }}</div>
                            <div class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $at ? $at->diffForHumans() : 'Never' }}</div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


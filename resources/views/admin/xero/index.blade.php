<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Xero Integration') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            @if(!$connection)
                <x-ui.card>
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-950 shadow-xl">
                            <span class="text-2xl font-extrabold text-white">X</span>
                        </div>
                        <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">Connect Xero to get started</h3>
                        <p class="mx-auto mb-7 mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                            Sync invoices, contacts, payments and repeating invoices across all organisations.
                        </p>
                        <a href="{{ route('admin.xero.connect') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            Connect to Xero
                        </a>
                    </div>
                </x-ui.card>
            @else
                <x-ui.hero
                    eyebrow="Connected · updated {{ optional($connection->updated_at)->diffForHumans() }}"
                    title="Global Xero connection"
                    :subtitle="$connection->tenants->where('is_active', true)->count() . ' active organisations'">
                    <x-slot name="actions">
                        <a href="{{ route('admin.xero.organisations.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                            Organisation hub
                        </a>
                        @if($connection->is_active)
                            <form method="POST" action="{{ route('admin.xero.refresh', $connection) }}">
                                @csrf
                                <button class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                                    Refresh
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.xero.disconnect', $connection) }}">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-xl">
                                    Disconnect
                                </button>
                            </form>
                        @else
                            <a href="{{ route('admin.xero.connect') }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                                Reconnect
                            </a>
                        @endif
                    </x-slot>
                </x-ui.hero>

                <div class="space-y-4">
                    @foreach($connection->tenants as $tenant)
                        <x-ui.card>
                            <div class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.xero.organisations.show', $tenant) }}" class="text-base font-extrabold text-gray-900 hover:text-[#F06927] dark:text-white">
                                            {{ $tenant->tenant_name }}
                                        </a>
                                        @if($tenant->is_active)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                        @endif
                                        @if($tenant->tenant_type)
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $tenant->tenant_type }}</span>
                                        @endif
                                        @if($tenant->company)
                                            <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $tenant->company->name }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 font-mono text-[11px] text-gray-400">ID: {{ $tenant->tenant_id }}</div>
                                    @if($tenant->dd_bank_account_id)
                                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Direct debit: {{ $tenant->dd_bank_account_name }}
                                        </div>
                                    @else
                                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            No direct debit bank account
                                        </div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.xero.tenants.sync', $tenant) }}" class="shrink-0">
                                    @csrf
                                    <select name="type" onchange="this.form.submit()"
                                            class="rounded-xl border-gray-200 bg-white px-3 py-2.5 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <option selected disabled>Sync…</option>
                                        <option value="contacts">Contacts</option>
                                        <option value="invoices">Invoices</option>
                                        <option value="repeating">Repeating invoices</option>
                                        <option value="all">Full sync</option>
                                        <option value="full">Full resync</option>
                                    </select>
                                </form>
                            </div>

                            <div class="grid grid-cols-2 gap-4 border-t border-gray-100 px-6 py-4 md:grid-cols-4 dark:border-gray-700">
                                @foreach(['Contacts' => $tenant->last_contact_synced_at, 'Invoices' => $tenant->last_invoice_synced_at, 'Payments' => $tenant->last_payment_synced_at, 'Repeating' => $tenant->last_repeating_invoice_synced_at] as $label => $at)
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">{{ $label }}</div>
                                        <div class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $at ? $at->diffForHumans() : 'Never' }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap gap-2 border-t border-gray-100 bg-gray-50/60 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/20">
                                <a href="{{ route('admin.xero.organisations.show', $tenant) }}"
                                   class="inline-flex items-center rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow dark:bg-slate-700">
                                    Open hub
                                </a>
                                <a href="{{ route('admin.xero.contacts', ['xeroConnection' => $connection, 'tenant' => $tenant]) }}"
                                   class="inline-flex items-center rounded-xl bg-[#F06927] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow">
                                    Reconcile contacts
                                </a>
                                <a href="{{ route('admin.xero.tenants.bank-settings', $tenant) }}"
                                   class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                    {{ $tenant->dd_bank_account_id ? 'Update DD account' : 'Setup DD account' }}
                                </a>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>


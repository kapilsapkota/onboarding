<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Xero Organisations') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Source of truth"
                title="Xero Organisations"
                subtitle="Every organisation linked to its company, default Stripe account and customer graph. Manage billing identity for Xero, Stripe and direct debit from here.">
                <x-slot name="actions">
                    @can('view-contact')
                        <a href="{{ route('admin.xero.reconcile.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                            Reconcile all customers
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endcan
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Organisations" :value="number_format($stats['total'])" accent="orange" />
                <x-ui.stat label="Active" :value="number_format($stats['active'])" accent="emerald" />
                <x-ui.stat label="Linked to a company" :value="number_format($stats['linked'])" accent="sky" />
                <x-ui.stat label="Unlinked" :value="number_format($stats['unlinked'])" accent="amber" />
            </div>

            <x-ui.card title="All organisations" subtitle="Attach each Xero organisation to its company and default Stripe account.">
                <x-slot name="actions">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="link" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All links</option>
                            <option value="linked" {{ request('link') === 'linked' ? 'selected' : '' }}>Linked</option>
                            <option value="unlinked" {{ request('link') === 'unlinked' ? 'selected' : '' }}>Unlinked</option>
                        </select>
                        <input name="search" value="{{ request('search') }}" placeholder="Search name or ID…"
                               class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-700">Filter</button>
                    </form>
                </x-slot>

                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($tenants as $tenant)
                        <li class="flex flex-col gap-4 px-6 py-5 transition hover:bg-gray-50 lg:flex-row lg:items-center dark:hover:bg-gray-700/40">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-lg font-extrabold text-white shadow-md">
                                {{ strtoupper(substr($tenant->tenant_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.xero.organisations.show', $tenant) }}" class="text-sm font-bold text-gray-900 hover:text-[#F06927] dark:text-gray-100">
                                        {{ $tenant->tenant_name }}
                                    </a>
                                    @if($tenant->is_active)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                    @endif
                                    @if($tenant->company)
                                        <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $tenant->company->name }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">No company</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    {{ $tenant->defaultStripeAccount?->display_name ?? 'No Stripe account' }}
                                    · {{ $tenant->contacts_count }} contacts · {{ $tenant->invoices_count }} invoices · {{ $tenant->repeating_invoices_count }} repeating
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <a href="{{ route('admin.xero.organisations.show', $tenant) }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-700">
                                    Open hub
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                                @can('configure-xero')
                                    <a href="{{ route('admin.xero.organisations.edit', $tenant) }}"
                                       class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                        Links
                                    </a>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No organisations found. Connect Xero to pull them in.</li>
                    @endforelse
                </ul>
            </x-ui.card>

            <div>{{ $tenants->links() }}</div>
        </div>
    </div>
</x-app-layout>


<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Xero Customers
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Xero contacts"
                title="Customers"
                subtitle="Every customer and supplier synced from Xero, with its matched client. Reconcile mismatches from one place.">
                <x-slot name="actions">
                    @can('sync-xero-contacts')
                        <form method="POST" action="{{ route('admin.xero.customers.sync-all') }}">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                                Sync all organisations
                            </button>
                        </form>
                    @endcan
                    @can('create-contact')
                        <a href="{{ route('admin.xero.customers.create', $tenant ? ['tenant' => $tenant->id] : []) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            New customer
                        </a>
                    @endcan
                    @can('view-contact')
                        <a href="{{ route('admin.xero.reconcile.index', $tenant ? ['tenant' => $tenant->id] : []) }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                            Reconcile →
                        </a>
                    @endcan
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Total" :value="number_format($stats['total'])" accent="slate" />
                <x-ui.stat label="Customers" :value="number_format($stats['customers'])" accent="sky" />
                <x-ui.stat label="Suppliers" :value="number_format($stats['suppliers'])" accent="violet" />
                <x-ui.stat label="Matched to clients" :value="number_format($stats['matched'])" accent="emerald" />
            </div>

            <x-ui.card title="All customers" :subtitle="$tenant ? $tenant->tenant_name : 'All organisations'">
                <x-slot name="actions">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="tenant" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All organisations</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ $tenant?->id === $t->id ? 'selected' : '' }}>{{ $t->tenant_name }}</option>
                            @endforeach
                        </select>
                        <select name="state" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="active" {{ request('state', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="archived" {{ request('state') === 'archived' ? 'selected' : '' }}>Archived</option>
                            <option value="all" {{ request('state') === 'all' ? 'selected' : '' }}>All</option>
                        </select>
                        <select name="kind" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All types</option>
                            <option value="customer" {{ request('kind') === 'customer' ? 'selected' : '' }}>Customers</option>
                            <option value="supplier" {{ request('kind') === 'supplier' ? 'selected' : '' }}>Suppliers</option>
                        </select>
                        <input name="search" value="{{ request('search') }}" placeholder="Name, email, number…"
                               class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-700">Filter</button>
                    </form>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Organisation</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Matched client</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($contacts as $contact)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.xero.customers.show', $contact) }}" class="font-bold text-gray-900 hover:text-[#F06927] dark:text-gray-100">
                                        {{ $contact->name }}
                                    </a>
                                    @if($contact->xero_contact_status === 'ARCHIVED')
                                        <span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-900/40 dark:text-rose-300">Archived</span>
                                    @endif
                                    <div class="text-xs text-gray-400">{{ $contact->xero_contact_number ?? '' }}</div>
                                </td>
                                <td class="px-4 py-4 text-gray-500">{{ $contact->email ?? '—' }}</td>
                                <td class="px-4 py-4 text-gray-500">{{ $contact->tenant?->tenant_name ?? '—' }}</td>
                                <td class="px-4 py-4">
                                    <span class="flex flex-wrap gap-1">
                                        @if($contact->is_customer)<span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">Customer</span>@endif
                                        @if($contact->is_supplier)<span class="rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-bold text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">Supplier</span>@endif
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-500">
                                    @if($contact->client)
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $contact->client->company_name }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.xero.customers.show', $contact) }}" class="text-xs font-bold text-[#F06927] hover:text-[#D14F1A]">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>

            <div>{{ $contacts->links() }}</div>
        </div>
    </div>
</x-app-layout>


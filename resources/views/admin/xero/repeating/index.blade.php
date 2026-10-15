<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Xero Repeating Invoices
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Recurring billing"
                title="Repeating invoices"
                subtitle="Templates that generate invoices on schedule, mirrored from Xero.">
                <x-slot name="actions">
                    @can('sync-repeating-invoice')
                        <form method="POST" action="{{ route('admin.xero.repeating-invoices.sync-all') }}">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                                Sync all organisations
                            </button>
                        </form>
                    @endcan
                    @can('create-repeating-invoice')
                        <a href="{{ route('admin.xero.repeating-invoices.create', $tenant ? ['tenant' => $tenant->id] : []) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            New template
                        </a>
                    @endcan
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
                <x-ui.stat label="Authorised templates" :value="number_format($stats['authorised'])" accent="sky" />
                <x-ui.stat label="Draft templates" :value="number_format($stats['draft'])" accent="slate" />
                <x-ui.stat label="Authorised value" :value="'$' . number_format($stats['monthly_total'], 2)" accent="emerald" />
            </div>

            <x-ui.card title="All templates" :subtitle="$tenant ? $tenant->tenant_name : 'All organisations'">
                <x-slot name="actions">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="tenant" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All organisations</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ $tenant?->id === $t->id ? 'selected' : '' }}>{{ $t->tenant_name }}</option>
                            @endforeach
                        </select>
                        <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All statuses</option>
                            @foreach(['DRAFT','AUTHORISED','DELETED'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <input name="search" value="{{ request('search') }}" placeholder="Reference, customer…"
                               class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-700">Filter</button>
                    </form>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-4 py-3">Organisation</th>
                            <th class="px-4 py-3">Schedule</th>
                            <th class="px-4 py-3">Next run</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($templates as $template)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.xero.repeating-invoices.show', $template) }}" class="font-bold text-gray-900 hover:text-[#F06927] dark:text-gray-100">
                                        {{ $template->xeroContact?->name ?? '—' }}
                                    </a>
                                    <div class="text-xs text-gray-400">{{ $template->reference ?? $template->type }}</div>
                                </td>
                                <td class="px-4 py-4 text-gray-500">{{ $template->tenant?->tenant_name ?? '—' }}</td>
                                <td class="px-4 py-4 text-gray-500">{{ $template->schedule_summary }}</td>
                                <td class="px-4 py-4 text-gray-500">{{ $template->schedule_next_scheduled_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-right font-semibold">{{ $template->currency_code }} {{ number_format($template->total, 2) }}</td>
                                <td class="px-4 py-4">
                                    @php($color = match($template->status) {
                                        'AUTHORISED' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                        'DRAFT' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        'DELETED' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    })
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $color }}">{{ $template->status }}</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.xero.repeating-invoices.show', $template) }}" class="text-xs font-bold text-[#F06927] hover:text-[#D14F1A]">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No repeating invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>

            <div>{{ $templates->links() }}</div>
        </div>
    </div>
</x-app-layout>


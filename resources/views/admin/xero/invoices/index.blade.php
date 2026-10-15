<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Xero Invoices
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Xero invoices"
                title="Invoices"
                subtitle="Every sales invoice and bill across all organisations, mirrored from Xero.">
                <x-slot name="actions">
                    @can('sync-invoice')
                        <form method="POST" action="{{ route('admin.xero.invoices.sync-all') }}">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                                Sync all organisations
                            </button>
                        </form>
                    @endcan
                    @can('create-invoice')
                        <a href="{{ route('admin.xero.invoices.create', $tenant ? ['tenant' => $tenant->id] : []) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            New invoice
                        </a>
                    @endcan
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Outstanding" :value="'$' . number_format($stats['outstanding'], 2)" accent="orange" />
                <x-ui.stat label="Overdue" :value="number_format($stats['overdue'])" accent="rose" />
                <x-ui.stat label="Draft" :value="number_format($stats['draft'])" accent="slate" />
                <x-ui.stat label="Authorised" :value="number_format($stats['authorised'])" accent="sky" />
            </div>

            <x-ui.card title="All invoices" :subtitle="$tenant ? $tenant->tenant_name : 'All organisations'">
                <x-slot name="actions">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="tenant" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All organisations</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ $tenant?->id === $t->id ? 'selected' : '' }}>{{ $t->tenant_name }}</option>
                            @endforeach
                        </select>
                        <select name="type" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All types</option>
                            <option value="ACCREC" {{ request('type') === 'ACCREC' ? 'selected' : '' }}>Sales invoice (ACCREC)</option>
                            <option value="ACCPAY" {{ request('type') === 'ACCPAY' ? 'selected' : '' }}>Bill (ACCPAY)</option>
                        </select>
                        <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All statuses</option>
                            @foreach(['DRAFT','SUBMITTED','AUTHORISED','PAID','VOIDED','DELETED'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <input name="search" value="{{ request('search') }}" placeholder="Number, reference, customer…"
                               class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-700">Filter</button>
                        @if(request()->hasAny(['tenant','type','status','search']))
                            <a href="{{ route('admin.xero.invoices.index') }}" class="text-xs font-semibold text-gray-400 underline hover:text-gray-600">Clear</a>
                        @endif
                    </form>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Number</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Organisation</th>
                            <th class="px-4 py-3">Date / Due</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Due</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($invoices as $invoice)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.xero.invoices.show', $invoice) }}" class="font-bold text-gray-900 hover:text-[#F06927] dark:text-gray-100">
                                        {{ $invoice->display_number }}
                                    </a>
                                    <div class="text-xs text-gray-400">{{ $invoice->type }}</div>
                                </td>
                                <td class="px-4 py-4">{{ $invoice->xeroContact?->name ?? '—' }}</td>
                                <td class="px-4 py-4 text-gray-500">{{ $invoice->tenant?->tenant_name ?? '—' }}</td>
                                <td class="px-4 py-4 text-xs text-gray-500">
                                    {{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}
                                    <span class="text-gray-400">→ {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-4 text-right">{{ $invoice->currency_code }} {{ number_format($invoice->total, 2) }}</td>
                                <td class="px-4 py-4 text-right font-bold {{ $invoice->amount_due > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ number_format($invoice->amount_due, 2) }}
                                </td>
                                <td class="px-4 py-4">
                                    @php($color = match($invoice->status) {
                                        'PAID' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'AUTHORISED' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                        'SUBMITTED' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'DRAFT' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        'VOIDED','DELETED' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    })
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $color }}">{{ $invoice->status }}</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.xero.invoices.show', $invoice) }}" class="text-xs font-bold text-[#F06927] hover:text-[#D14F1A]">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No invoices found. Sync an organisation or create one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>

            <div>{{ $invoices->links() }}</div>
        </div>
    </div>
</x-app-layout>


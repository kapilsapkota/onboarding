<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $customer->name }}
        </h2>
    </x-slot>

    @php($archived = $customer->xero_contact_status === 'ARCHIVED')

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                :eyebrow="($archived ? 'Archived · ' : 'Active · ') . ($customer->tenant?->tenant_name ?? 'Unknown organisation')"
                :title="$customer->name"
                :subtitle="'Last synced ' . ($customer->synced_at?->diffForHumans() ?? 'never')">
                <x-slot name="actions">
                    <a href="{{ route('admin.xero.customers.index', $customer->tenant ? ['tenant' => $customer->xero_tenant_id] : []) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Back to customers
                    </a>
                    @if(!$archived)
                        @can('edit-contact')
                            <a href="{{ route('admin.xero.customers.edit', $customer) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                                Edit in Xero
                            </a>
                        @endcan
                        @can('delete-contact')
                            <form method="POST" action="{{ route('admin.xero.customers.destroy', $customer) }}"
                                  onsubmit="return confirm('Archive this customer in Xero? Local links stay intact.')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-xl">Archive</button>
                            </form>
                        @endcan
                    @else
                        @can('edit-contact')
                            <form method="POST" action="{{ route('admin.xero.customers.restore', $customer) }}">
                                @csrf
                                <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-xl">Restore in Xero</button>
                            </form>
                        @endcan
                    @endif
                </x-slot>
            </x-ui.hero>

            <div class="grid gap-4 md:grid-cols-3">
                <x-ui.card title="Contact details">
                    <dl class="space-y-2.5 px-6 py-5 text-sm">
                        @foreach(['Email' => $customer->email, 'Phone' => $customer->phone, 'Contact no' => $customer->xero_contact_number, 'Account no' => $customer->xero_account_number, 'Currency' => $customer->default_currency] as $label => $value)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-xs font-bold uppercase tracking-[0.12em] text-gray-400">{{ $label }}</dt>
                                <dd class="font-semibold text-gray-900 dark:text-white">{{ $value ?? '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>
                <x-ui.card title="Type & tax">
                    <dl class="space-y-2.5 px-6 py-5 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-gray-400">Customer</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $customer->is_customer ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-gray-400">Supplier</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $customer->is_supplier ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-gray-400">Tax no</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $customer->tax_number ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-gray-400">Bank</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $customer->bank_account_details ?? '—' }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
                <x-ui.card title="Matched client">
                    <div class="px-6 py-5 text-sm">
                        @if($customer->client)
                            <a href="{{ route('clients.show', $customer->client) }}" class="text-base font-extrabold text-gray-900 hover:text-[#F06927] dark:text-white">{{ $customer->client->company_name }}</a>
                            <div class="mt-1 text-xs text-gray-400">via {{ $customer->match_method ?? '—' }} ({{ round($customer->match_score ?? 0) }}%)</div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">Not matched to a local client.</p>
                            <a href="{{ route('admin.xero.reconcile.index', ['tenant' => $customer->xero_tenant_id]) }}"
                               class="mt-3 inline-flex items-center rounded-xl bg-[#F06927] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow">
                                Reconcile now →
                            </a>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card title="Recent invoices">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr><th class="px-6 py-3">Number</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Due</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($invoices as $invoice)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4"><a href="{{ route('admin.xero.invoices.show', $invoice) }}" class="font-bold text-[#F06927] hover:text-[#D14F1A]">{{ $invoice->display_number }}</a></td>
                                <td class="px-4 py-4 text-gray-500">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($invoice->total, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold {{ $invoice->amount_due > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($invoice->amount_due, 2) }}</td>
                                <td class="px-4 py-4 text-xs font-bold text-gray-500">{{ $invoice->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No invoices for this customer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


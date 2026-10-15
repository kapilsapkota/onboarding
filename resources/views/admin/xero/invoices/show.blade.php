<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Invoice {{ $invoice->display_number }}
        </h2>
    </x-slot>

    @php($editable = in_array($invoice->status, ['DRAFT','SUBMITTED'], true))

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                :eyebrow="$invoice->type . ' · ' . $invoice->status . ' · synced ' . ($invoice->last_synced_at?->diffForHumans() ?? 'never')"
                :title="'Invoice ' . $invoice->display_number"
                :subtitle="($invoice->xeroContact?->name ?? 'Unknown customer') . ' · ' . ($invoice->tenant?->tenant_name ?? '') . ($invoice->reference ? ' · Ref ' . $invoice->reference : '')">
                <x-slot name="actions">
                    <a href="{{ route('admin.xero.invoices.index', $invoice->tenant ? ['tenant' => $invoice->xero_tenant_id] : []) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Back to invoices
                    </a>
                    @if($editable)
                        @can('edit-invoice')
                            <a href="{{ route('admin.xero.invoices.edit', $invoice) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                                Edit in Xero
                            </a>
                            <form method="POST" action="{{ route('admin.xero.invoices.authorise', $invoice) }}"
                                  onsubmit="return confirm('Authorise this invoice in Xero? Authorised invoices can no longer be edited.')">
                                @csrf
                                <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-xl">Authorise</button>
                            </form>
                        @endcan
                        @can('delete-invoice')
                            <form method="POST" action="{{ route('admin.xero.invoices.destroy', $invoice) }}"
                                  onsubmit="return confirm('Delete this draft invoice in Xero? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-xl">Delete draft</button>
                            </form>
                        @endcan
                    @elseif($invoice->status === 'AUTHORISED')
                        @can('delete-invoice')
                            <form method="POST" action="{{ route('admin.xero.invoices.destroy', $invoice) }}"
                                  onsubmit="return confirm('Void this authorised invoice in Xero? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-xl">Void invoice</button>
                            </form>
                        @endcan
                    @endif
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Amount due" :value="$invoice->currency_code . ' ' . number_format($invoice->amount_due, 2)" accent="{{ $invoice->amount_due > 0 ? 'rose' : 'emerald' }}" />
                <x-ui.stat label="Total" :value="number_format($invoice->total, 2)" :sub="'Paid ' . number_format($invoice->amount_paid, 2)" accent="slate" />
                <x-ui.stat label="Invoice date" :value="$invoice->invoice_date?->format('d/m/Y') ?? '—'" :sub="'Due ' . ($invoice->due_date?->format('d/m/Y') ?? '—')" accent="sky" />
                <x-ui.stat label="Direct debit" :value="$invoice->latestDirectDebitPayment?->status ?? 'No run'" accent="orange" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.card title="Customer">
                    <div class="px-6 py-5 text-sm">
                        @if($invoice->xeroContact)
                            <a href="{{ route('admin.xero.customers.show', $invoice->xeroContact) }}" class="text-base font-extrabold text-gray-900 hover:text-[#F06927] dark:text-white">{{ $invoice->xeroContact->name }}</a>
                            <div class="mt-1 text-xs text-gray-400">{{ $invoice->xeroContact->email ?? 'No email' }}</div>
                        @else
                            <span class="text-gray-400">Not linked</span>
                        @endif
                        @if($invoice->client)
                            <div class="mt-2 text-xs">Matched client: <span class="font-bold">{{ $invoice->client->company_name }}</span></div>
                        @endif
                    </div>
                </x-ui.card>
                <x-ui.card title="Billing chain">
                    <div class="px-6 py-5 text-sm">
                        @if($invoice->repeatingInvoice)
                            <div>From template:
                                <a href="{{ route('admin.xero.repeating-invoices.show', $invoice->repeatingInvoice) }}" class="font-bold text-[#F06927] hover:text-[#D14F1A]">view template →</a>
                            </div>
                        @else
                            <div class="text-gray-400">One-off invoice (no template).</div>
                        @endif
                        @if($invoice->latestDirectDebitPayment)
                            <div class="mt-1 text-xs text-gray-400">Latest run {{ $invoice->latestDirectDebitPayment->created_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card title="Line items">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr><th class="px-6 py-3">Description</th><th class="px-4 py-3">Qty</th><th class="px-4 py-3 text-right">Unit</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Tax</th><th class="px-4 py-3 text-right">Amount</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($invoice->line_items ?? [] as $line)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4">{{ $line['description'] ?? '—' }}</td>
                                <td class="px-4 py-4">{{ $line['quantity'] ?? '—' }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($line['unit_amount'] ?? 0, 2) }}</td>
                                <td class="px-4 py-4 font-mono text-xs text-gray-500">{{ $line['account_code'] ?? '—' }}</td>
                                <td class="px-4 py-4 text-gray-500">{{ $line['tax_type'] ?? '—' }}</td>
                                <td class="px-4 py-4 text-right font-semibold">{{ number_format($line['line_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No line items stored.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="space-y-1 border-t border-gray-100 px-6 py-4 text-right text-sm dark:border-gray-700">
                    <div class="text-gray-500">Subtotal: {{ number_format($invoice->sub_total, 2) }}</div>
                    <div class="text-gray-500">Tax: {{ number_format($invoice->total_tax, 2) }}</div>
                    <div class="text-base font-extrabold text-gray-900 dark:text-white">Total: {{ $invoice->currency_code }} {{ number_format($invoice->total, 2) }}</div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


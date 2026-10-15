<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Repeating invoice · {{ $template->xeroContact?->name ?? 'Unknown' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                :eyebrow="$template->status . ' · synced ' . ($template->last_synced_at?->diffForHumans() ?? 'never')"
                :title="$template->schedule_summary"
                :subtitle="($template->xeroContact?->name ?? 'Unknown customer') . ' · ' . ($template->tenant?->tenant_name ?? '')">
                <x-slot name="actions">
                    <a href="{{ route('admin.xero.repeating-invoices.index', $template->tenant ? ['tenant' => $template->xero_tenant_id] : []) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Back to templates
                    </a>
                    @if($template->status === 'DRAFT')
                        @can('edit-repeating-invoice')
                            <a href="{{ route('admin.xero.repeating-invoices.edit', $template) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                                Edit in Xero
                            </a>
                            <form method="POST" action="{{ route('admin.xero.repeating-invoices.authorise', $template) }}"
                                  onsubmit="return confirm('Authorise this template? Xero will start generating invoices on schedule.')">
                                @csrf
                                <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-xl">Authorise</button>
                            </form>
                        @endcan
                    @endif
                    @if($template->status !== 'DELETED')
                        @can('delete-repeating-invoice')
                            <form method="POST" action="{{ route('admin.xero.repeating-invoices.destroy', $template) }}"
                                  onsubmit="return confirm('Delete this repeating invoice template in Xero? Generated invoices stay intact.')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-xl">Delete template</button>
                            </form>
                        @endcan
                    @endif
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Per run" :value="$template->currency_code . ' ' . number_format($template->total, 2)" accent="orange" />
                <x-ui.stat label="Starts" :value="$template->schedule_start_date?->format('d/m/Y') ?? '—'" accent="sky" />
                <x-ui.stat label="Next run" :value="$template->schedule_next_scheduled_date?->format('d/m/Y') ?? '—'" accent="emerald" />
                <x-ui.stat label="Generated" :value="number_format($template->generatedInvoices->count())" accent="violet" />
            </div>

            @if($template->status === 'AUTHORISED')
                <p class="text-xs italic text-gray-400">Authorised templates cannot be edited via the API — delete and recreate to change terms.</p>
            @endif

            <x-ui.card title="Template line items">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr><th class="px-6 py-3">Description</th><th class="px-4 py-3">Qty</th><th class="px-4 py-3 text-right">Unit</th><th class="px-4 py-3 text-right">Amount</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($template->line_items ?? [] as $line)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4">{{ $line['description'] ?? '—' }}</td>
                                <td class="px-4 py-4">{{ $line['quantity'] ?? '—' }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($line['unit_amount'] ?? 0, 2) }}</td>
                                <td class="px-4 py-4 text-right font-semibold">{{ number_format($line['line_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No line items stored.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>

            <x-ui.card title="Generated invoices" subtitle="Invoices Xero raised from this template.">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr><th class="px-6 py-3">Number</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($template->generatedInvoices as $invoice)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-6 py-4"><a href="{{ route('admin.xero.invoices.show', $invoice) }}" class="font-bold text-[#F06927] hover:text-[#D14F1A]">{{ $invoice->display_number }}</a></td>
                                <td class="px-4 py-4 text-gray-500">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-right">{{ number_format($invoice->total, 2) }}</td>
                                <td class="px-4 py-4 text-xs font-bold text-gray-500">{{ $invoice->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No generated invoices linked yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


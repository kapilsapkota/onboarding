<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Hero --}}
            <div class="relative overflow-hidden rounded-3xl bg-slate-950 shadow-2xl">
                <div class="relative flex flex-col gap-6 px-6 py-8 sm:px-10 sm:py-10 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#F5A97B]">{{ now()->format('l, d F Y') }}</p>
                        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}{{ auth()->user() ? ', '.auth()->user()->name : '' }}
                        </h1>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">
                            Bulk direct debit collections, mandates and client links, all one click away.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('admin.stripe.bulk-charge') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m0 0l-6-6m6 6l6-6"/></svg>
                            New bulk charge
                        </a>
                        <a href="{{ route('admin.stripe.batches.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                            View batches
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Bulk direct debit stats --}}
            <div>
                <div class="mb-3 flex items-center justify-between px-1">
                    <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Bulk Direct Debit</h3>
                    <a href="{{ route('admin.stripe.batches.index') }}" class="text-xs font-semibold text-[#F06927] hover:text-[#D14F1A]">Open bulk direct debit →</a>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    <a href="{{ route('admin.stripe.batches.index', ['view' => 'items', 'status' => ['succeeded']]) }}"
                       class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800 dark:ring-gray-700">
                        <div class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></div>
                        <div class="flex items-center justify-between">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-md">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 group-hover:text-emerald-600">View collected →</span>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">${{ number_format($bulkCollectedCents / 100, 2) }}</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Collected · {{ number_format($bulkCollectedCount) }} payments</div>
                    </a>

                    <a href="{{ route('admin.stripe.batches.index', ['view' => 'items', 'status' => ['pending', 'processing']]) }}"
                       class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800 dark:ring-gray-700">
                        <div class="absolute inset-x-0 top-0 h-1 bg-[#F06927]"></div>
                        <div class="flex items-center justify-between">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F06927] text-white shadow-md">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 group-hover:text-[#F06927]">View in flight →</span>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ number_format($bulkProcessingCount) }}</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">In flight · pending &amp; processing</div>
                    </a>

                    <a href="{{ route('admin.stripe.batches.index', ['view' => 'items', 'status' => ['failed', 'disputed']]) }}"
                       class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800 dark:ring-gray-700">
                        <div class="absolute inset-x-0 top-0 h-1 bg-rose-500"></div>
                        <div class="flex items-center justify-between">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-md">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 group-hover:text-rose-600">Review failures →</span>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ number_format($bulkFailedCount) }}</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Needs attention · failed &amp; disputed</div>
                    </a>

                    <a href="{{ route('admin.stripe.batches.index', ['view' => 'batches']) }}"
                       class="group relative overflow-hidden rounded-2xl bg-slate-950 p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-2xl dark:ring-1 dark:ring-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F06927] text-white shadow-md">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 group-hover:text-[#F5A97B]">All batches →</span>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold tracking-tight text-white">{{ number_format($bulkBatchCount) }}</div>
                        <div class="mt-1 text-sm text-slate-400">Bulk batches · tap for history</div>
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Stripe Accounts - DDR Links</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Quickly open or copy each account's customer DDR form link.</p>
                    </div>
                    @can('view-stripe-account')
                        <a href="{{ route('admin.stripe-accounts.index') }}" class="text-xs font-semibold text-[#F06927] hover:text-[#D14F1A]">Manage accounts →</a>
                    @endcan
                </div>

                @if (($stripeAccounts ?? collect())->isEmpty())
                    <p class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">No active Stripe accounts available.</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($stripeAccounts as $account)
                            @php $ddrUrl = $account->ddrUrl(); @endphp
                            <li class="flex flex-col gap-3 px-6 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center dark:hover:bg-gray-700/40">
                                <div class="flex shrink-0 items-center justify-center">
                                    @if ($account->company?->logo)
                                        <img src="{{ asset('images/' . $account->company->logo) }}"
                                             alt="{{ $account->company->name }} logo"
                                             class="h-10 w-10 rounded-full border border-gray-200 bg-white object-contain p-0.5 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#F06927] text-sm font-bold text-white shadow-sm" title="{{ $account->company?->name ?? 'No company' }}">
                                            {{ strtoupper(substr($account->company?->name ?? $account->display_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $account->display_name }}</span>
                                        @if ($account->is_default)
                                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Default</span>
                                        @endif
                                        @if ($account->is_legacy)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Legacy</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $account->company?->name ?? 'No company' }} · #{{ $account->id }}</div>
                                    <a href="{{ $ddrUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate font-mono text-[11px] text-gray-400 hover:text-[#F06927] hover:underline dark:text-gray-500">{{ $ddrUrl }}</a>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <a href="{{ $ddrUrl }}" target="_blank" rel="noopener noreferrer" title="Open DDR page" class="inline-flex items-center gap-1.5 rounded-lg bg-[#F06927] px-3 py-2 text-xs font-medium text-white transition hover:bg-[#D14F1A]">
                                        Open
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <button type="button" onclick="copyDashboardDdr(@js($ddrUrl), this)" title="Copy DDR link" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 002 2v8a2 2 0 002 2z"/></svg>
                                        <span>Copy</span>
                                    </button>
                                    <button type="button" onclick="openWhatsappShare(@js($ddrUrl), @js($account->display_name), @js($account->company?->name))" title="Share DDR link via WhatsApp" class="inline-flex items-center gap-1.5 rounded-lg bg-[#25D366] px-3 py-2 text-xs font-medium text-white transition hover:bg-[#1DA851]">
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        <span>WhatsApp</span>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Recent batches --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Recent bulk batches</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Latest runs — click any batch for full detail.</p>
                    </div>
                    <a href="{{ route('admin.stripe.batches.index', ['view' => 'batches']) }}" class="text-xs font-semibold text-[#F06927] hover:text-[#D14F1A]">View all →</a>
                </div>
                @if (($recentBatches ?? collect())->isEmpty())
                    <p class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">No bulk batches yet. <a href="{{ route('admin.stripe.bulk-charge') }}" class="font-semibold text-[#F06927] hover:underline">Start your first bulk charge →</a></p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($recentBatches as $batch)
                            @php
                                $pct = $batch->items_count > 0 ? (int) round($batch->succeeded_items_count / $batch->items_count * 100) : 0;
                                $statusTheme = match ($batch->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'processing' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    'completed_with_errors' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                    default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                                };
                            @endphp
                            <li>
                                <a href="{{ route('admin.stripe.batches.show', $batch) }}" class="flex flex-col gap-3 px-6 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center dark:hover:bg-gray-700/40">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $batch->reference ?? 'Batch #'.$batch->id }}</span>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusTheme }}">{{ str_replace('_', ' ', $batch->status) }}</span>
                                        </div>
                                        <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $batch->stripeAccount?->display_name ?? 'Bulk charge' }} · {{ $batch->items_count }} payments · {{ $batch->formattedTotal() }} · {{ $batch->created_at?->diffForHumans() }}</div>
                                        <div class="mt-2 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $batch->succeeded_items_count }}/{{ $batch->items_count }} collected</span>
                                        <svg class="h-4 w-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Portfolio strip --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <a href="{{ route('clients.index') }}"
                   class="rounded-2xl bg-white p-5 shadow ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-gray-800 dark:ring-gray-700">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Clients</div>
                    <div class="mt-1 text-2xl font-extrabold text-[#F06927]">{{ number_format($totalClients) }}</div>
                </a>
                <a href="{{ route('admin.xero.index') }}"
                   class="rounded-2xl bg-white p-5 shadow ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-gray-800 dark:ring-gray-700">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Xero Invoices</div>
                    <div class="mt-1 text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ number_format($totalInvoices) }}</div>
                </a>
                <a href="{{ route('admin.directDebitPayment.index') }}"
                   class="rounded-2xl bg-white p-5 shadow ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-gray-800 dark:ring-gray-700">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Settled Direct Debits</div>
                    <div class="mt-1 text-2xl font-extrabold text-green-600 dark:text-green-400">{{ number_format($successfulDebits) }}</div>
                </a>
                <a href="{{ route('admin.directDebitPayment.index') }}"
                   class="rounded-2xl bg-white p-5 shadow ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-gray-800 dark:ring-gray-700">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Failed Direct Debits</div>
                    <div class="mt-1 text-2xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($failedDebits) }}</div>
                </a>
            </div>

        </div>
    </div>

    <div id="whatsapp-share-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60" onclick="closeWhatsappShare()"></div>
        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Share DDR via WhatsApp</h3>
                    <p id="whatsapp-share-subtitle" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Edit the message, then open WhatsApp to pick a person or group.</p>
                </div>
                <button type="button" onclick="closeWhatsappShare()" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4">
                <label for="whatsapp-share-text" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Message</label>
                <textarea id="whatsapp-share-text" rows="8" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-green-600 focus:outline-none focus:ring-1 focus:ring-green-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"></textarea>
                <p class="mt-2 text-[11px] text-gray-400 dark:text-gray-500">Opens WhatsApp (desktop app or web) where you can choose a person or group. Nothing is sent until you press send in WhatsApp.</p>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/30">
                <button type="button" onclick="closeWhatsappShare()" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
                <button type="button" onclick="copyWhatsappText(this)" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"><span>Copy text</span></button>
                <button type="button" onclick="shareViaWhatsapp()" class="inline-flex items-center gap-2 rounded-lg bg-[#25D366] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1DA851]">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Open WhatsApp
                </button>
            </div>
        </div>
    </div>

    <script>
        function defaultWhatsappText(ddrUrl, displayName, companyName) {
            const from = companyName ? companyName + ' (' + displayName + ')' : displayName;
            // URL on its own line: WhatsApp only linkifies + previews bare URLs
            // when separated by whitespace/newlines, not glued to sentence text.
            return 'Hi,\n\nWe urgently need to update your direct debit details for ongoing services.\n\nPlease complete the form here:\n' + ddrUrl + '\n\nThanks,\n' + from;
        }

        function openWhatsappShare(ddrUrl, displayName, companyName) {
            const modal = document.getElementById('whatsapp-share-modal');
            const text = document.getElementById('whatsapp-share-text');
            const subtitle = document.getElementById('whatsapp-share-subtitle');
            text.value = defaultWhatsappText(ddrUrl, displayName, companyName || '');
            subtitle.textContent = (companyName ? companyName + ' — ' : '') + displayName + ': edit the message, then open WhatsApp to pick a person or group.';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => text.focus(), 50);
        }

        function closeWhatsappShare() {
            const modal = document.getElementById('whatsapp-share-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function shareViaWhatsapp() {
            const text = document.getElementById('whatsapp-share-text').value;
            window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank', 'noopener');
        }

        function copyWhatsappText(button) {
            const text = document.getElementById('whatsapp-share-text').value;
            const label = button.querySelector('span');
            const original = label ? label.textContent : button.textContent.trim();
            const done = (msg) => {
                if (label) {
                    label.textContent = msg;
                }
                setTimeout(() => {
                    if (label) {
                        label.textContent = original;
                    }
                }, 2000);
            };
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(text).then(() => done('Copied!')).catch(() => done('Failed'));
            } else {
                done('Failed');
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeWhatsappShare();
            }
        });
        function copyDashboardDdr(text, button) {
            const label = button.querySelector('span');
            const original = label ? label.textContent : button.textContent.trim();
            const done = (msg) => {
                if (label) {
                    label.textContent = msg;
                }
                setTimeout(() => {
                    if (label) {
                        label.textContent = original;
                    }
                }, 2000);
            };
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(text).then(() => done('Copied!')).catch(() => done('Failed'));
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    done('Copied!');
                } catch (e) {
                    done('Failed');
                }
                ta.remove();
            }
        }
    </script>


</x-app-layout>

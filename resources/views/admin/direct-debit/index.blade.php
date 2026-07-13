<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Direct Debit Payments
        </h2>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">
        <x-alert></x-alert>

        {{-- STATS --}}
        @php
            $stats = [
                'total'      => $payments->total(),
                'pending'    => \App\Models\DirectDebitPayment::where('status', 'pending')->count(),
                'processing' => \App\Models\DirectDebitPayment::where('status', 'processing')->count(),
                'settled'    => \App\Models\DirectDebitPayment::where('status', 'settled')->count(),
                'failed'     => \App\Models\DirectDebitPayment::where('status', 'failed')->count(),
                'awaiting'   => \App\Models\DirectDebitPayment::awaitingXeroPostback()->count(),
            ];
        @endphp

        <div class="flex gap-3 mb-5 w-full">
            <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                <div class="text-xs text-gray-500 mt-1">Total (filtered)</div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 shadow-sm border border-yellow-100 text-center flex-1">
                <div class="text-2xl font-bold text-yellow-700">{{ number_format($stats['pending']) }}</div>
                <div class="text-xs text-yellow-600 mt-1">Pending</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100 text-center flex-1">
                <div class="text-2xl font-bold text-blue-700">{{ number_format($stats['processing']) }}</div>
                <div class="text-xs text-blue-600 mt-1">Processing</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 shadow-sm border border-green-100 text-center flex-1">
                <div class="text-2xl font-bold text-green-700">{{ number_format($stats['settled']) }}</div>
                <div class="text-xs text-green-600 mt-1">Settled</div>
            </div>
            <div class="bg-red-50 rounded-lg p-3 shadow-sm border border-red-100 text-center flex-1">
                <div class="text-2xl font-bold text-red-700">{{ number_format($stats['failed']) }}</div>
                <div class="text-xs text-red-600 mt-1">Failed</div>
            </div>
            <div class="bg-orange-50 rounded-lg p-3 shadow-sm border border-orange-100 text-center flex-1">
                <div class="text-2xl font-bold text-orange-700">{{ number_format($stats['awaiting']) }}</div>
                <div class="text-xs text-orange-600 mt-1">Awaiting Xero</div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="bg-white shadow-sm rounded-lg p-5 mb-4">
            <form method="GET"
                  action="{{ route('admin.directDebitPayment.index') }}"
                  class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

                {{-- SEARCH --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Invoice, ref, client…"
                           class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                </div>

                {{-- STATUS --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status"
                            class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                        <option value="">All statuses</option>
                        @foreach(['pending','processing','settled','failed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- XERO POSTED --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Xero posted</label>
                    <select name="xero_posted"
                            class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                        <option value="">Any</option>
                        <option value="yes" {{ request('xero_posted') === 'yes' ? 'selected' : '' }}>Posted</option>
                        <option value="no" {{ request('xero_posted') === 'no' ? 'selected' : '' }}>Not posted</option>
                        <option value="failed" {{ request('xero_posted') === 'failed' ? 'selected' : '' }}>Post failed</option>
                    </select>
                </div>

                {{-- INITIATED BY --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Initiated by</label>
                    <select name="initiated_by"
                            class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                        <option value="">Any</option>
                        <option value="scheduled" {{ request('initiated_by') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="manual" {{ request('initiated_by') === 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>

                {{-- DATE FROM --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                </div>

                {{-- DATE TO --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
                </div>

                {{-- BUTTONS --}}
                <div class="flex gap-2 md:col-span-6 justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                        Apply filters
                    </button>

                    <a href="{{ route('admin.directDebitPayment.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-600 rounded-md text-sm hover:bg-gray-200">
                        Reset
                    </a>
                </div>

            </form>
        </div>
        {{-- TABLE --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="p-3">Invoice</th>
                    <th class="p-3">Client</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Gateway</th>
                    <th class="p-3">Reference</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Xero</th>
                    <th class="p-3">Initiated</th>
                    <th class="p-3">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($payments as $payment)
                    @php
                        $statusConfig = [
                            'pending'    => ['bg-yellow-100 text-yellow-700', 'Pending'],
                            'processing' => ['bg-blue-100 text-blue-700',     'Processing'],
                            'settled'    => ['bg-green-100 text-green-700',   'Settled'],
                            'failed'     => ['bg-red-100 text-red-700',       'Failed'],
                            'cancelled'  => ['bg-gray-100 text-gray-600',     'Cancelled'],
                        ];
                        [$statusClass, $statusLabel] = $statusConfig[$payment->status] ?? ['bg-gray-100 text-gray-500', ucfirst($payment->status)];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">

                        <td class="p-3">
                            <div class="font-medium text-gray-800">{{ $payment->xero_invoice_number ?? '—' }}</div>
                            @if($payment->retry_of_id)
                                <div class="text-xs text-orange-500 mt-0.5">↩ Retry #{{ $payment->attempt_number }}</div>
                            @elseif($payment->attempt_number > 1)
                                <div class="text-xs text-orange-500 mt-0.5">Attempt #{{ $payment->attempt_number }}</div>
                            @endif
                        </td>

                        <td class="p-3">
                            <div class="font-medium text-gray-800">{{ $payment->client?->company_name ?? '—' }}</div>
                            <div class="text-xs text-gray-400">{{ $payment->client?->email ?? '' }}</div>
                        </td>

                        <td class="p-3">
                            <div class="font-medium text-gray-800">
                                {{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}
                            </div>
                            @if($payment->stripe_fee)
                                <div class="text-xs text-gray-400">
                                    Fee: {{ number_format($payment->stripe_fee, 2) }}
                                    / Net: {{ number_format($payment->stripe_net, 2) }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3">
                            <div class="text-gray-700">{{ ucwords(str_replace('_', ' ', $payment->gateway ?? '—')) }}</div>
                            @if($payment->gateway_payment_id)
                                <div class="text-xs text-gray-400 font-mono truncate max-w-[120px]" title="{{ $payment->gateway_payment_id }}">
                                    {{ $payment->gateway_payment_id }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3">
                            <div class="text-gray-700 font-mono text-xs">{{ $payment->our_reference ?? '—' }}</div>
                        </td>

                        <td class="p-3">
                            <span class="px-2 py-1 text-xs rounded-full font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                            @if($payment->status === 'failed' && $payment->failure_reason)
                                <div class="text-xs text-red-500 mt-1 max-w-[140px]" title="{{ $payment->failure_reason }}">
                                    {{ Str::limit($payment->failure_reason, 40) }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3">
                            @if($payment->xero_payment_id)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full font-medium">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                                    Posted
                                </span>
                            @elseif($payment->xero_post_attempted && $payment->xero_post_error)
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full font-medium" title="{{ $payment->xero_post_error }}">
                                    ✕ Failed
                                </span>
                            @elseif($payment->status === 'settled')
                                <span class="px-2 py-1 text-xs bg-orange-100 text-orange-600 rounded-full font-medium">
                                    Pending
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        <td class="p-3">
                            <div class="text-gray-700 text-xs">
                                {{ $payment->initiated_at?->format('d/m/Y H:i') ?? '—' }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                @if($payment->initiated_by_type === 'manual' && $payment->initiatedByUser)
                                    {{ $payment->initiatedByUser->name }}
                                @else
                                    Scheduled
                                @endif
                            </div>
                        </td>

                        <td class="p-3">
                            <a href="{{ route('admin.directDebitPayment.show', $payment) }}"
                               class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                View
                            </a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center text-gray-400">
                            No payments found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="mt-4">
            {{ $payments->withQueryString()->links() }}
        </div>

    </div>
</x-app-layout>

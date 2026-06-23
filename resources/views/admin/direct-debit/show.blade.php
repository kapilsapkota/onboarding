<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Payment - {{ $payment->our_reference ?? '#' . $payment->id }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">
        <x-alert></x-alert>

        {{-- BACK + ACTIONS --}}
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <a href="{{ route('admin.directDebitPayment.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">← Back to payments</a>
            <div class="flex gap-2 flex-wrap">
                @if($payment->status === 'settled' && !$payment->xero_payment_id)
                    <form method="POST" action="{{ route('admin.directDebitPayment.post-to-xero', $payment) }}">
                        @csrf
                        <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                            Post to Xero
                        </button>
                    </form>
                @endif
                @if(in_array($payment->status, ['failed', 'cancelled']))
                    <form method="POST" action="{{ route('admin.directDebitPayment.retry', $payment) }}">
                        @csrf
                        <button class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            Retry Payment
                        </button>
                    </form>
                @endif
                @if($payment->status === 'pending')
                    <form method="POST" action="{{ route('admin.directDebitPayment.cancel', $payment) }}">
                        @csrf
                        <button type="button" class="cancel-btn px-4 py-2 text-sm bg-red-50 text-red-600 rounded hover:bg-red-100 border border-red-200">
                            Cancel Payment
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- STATUS BANNER --}}
        @php
            $bannerConfig = [
                'pending'    => ['bg-yellow-50 border-yellow-200', 'text-yellow-800', 'text-yellow-600', '⏳ Pending'],
                'processing' => ['bg-blue-50 border-blue-200',     'text-blue-800',   'text-blue-600',   '⚙ Processing'],
                'settled'    => ['bg-green-50 border-green-200',   'text-green-800',  'text-green-600',  '✓ Settled'],
                'failed'     => ['bg-red-50 border-red-200',       'text-red-800',    'text-red-600',    '✕ Failed'],
                'cancelled'  => ['bg-gray-50 border-gray-200',     'text-gray-700',   'text-gray-500',   '— Cancelled'],
            ];
            [$bannerBg, $bannerTitle, $bannerSub, $bannerLabel] = $bannerConfig[$payment->status] ?? ['bg-gray-50 border-gray-200','text-gray-700','text-gray-500',ucfirst($payment->status)];
        @endphp

        <div class="rounded-lg border p-4 mb-5 {{ $bannerBg }}">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <span class="text-lg font-semibold {{ $bannerTitle }}">{{ $bannerLabel }}</span>
                    @if($payment->status === 'failed' && $payment->failure_reason)
                        <p class="text-sm {{ $bannerSub }} mt-1">
                            {{ $payment->failure_reason }}
                            @if($payment->failure_code)<span class="font-mono">({{ $payment->failure_code }})</span>@endif
                        </p>
                    @endif
                    @if($payment->settled_at)
                        <p class="text-sm {{ $bannerSub }} mt-1">Settled {{ $payment->settled_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold {{ $bannerTitle }}">
                        {{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}
                    </div>
                    @if($payment->attempt_number > 1)
                        <div class="text-xs {{ $bannerSub }} mt-1">Attempt #{{ $payment->attempt_number }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ROW 1: Payment details + Client & Invoice --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-700 text-sm">Payment details</h3>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['Our reference',  $payment->our_reference],
                        ['Method',         ucwords(str_replace('_', ' ', $payment->payment_method ?? ''))],
                        ['Gateway',        ucwords(str_replace('_', ' ', $payment->gateway ?? ''))],
                        ['Currency',       $payment->currency_code],
                        ['Initiated at',   $payment->initiated_at?->format('d/m/Y H:i')],
                        ['Submitted at',   $payment->submitted_to_gateway_at?->format('d/m/Y H:i')],
                        ['Settled at',     $payment->settled_at?->format('d/m/Y H:i')],
                        ['Failed at',      $payment->failed_at?->format('d/m/Y H:i')],
                        ['Cancelled at',   $payment->cancelled_at?->format('d/m/Y H:i')],
                        ['Initiated by',   $payment->initiated_by_type === 'manual' && $payment->initiatedByUser
                                            ? 'Manual - ' . $payment->initiatedByUser->name
                                            : 'Scheduled'],
                    ] as [$label, $value])
                        @if($value)
                            <tr>
                                <td class="px-4 py-2.5 text-gray-500 w-36">{{ $label }}</td>
                                <td class="px-4 py-2.5 text-gray-800 font-medium">{{ $value }}</td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-700 text-sm">Client & invoice</h3>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-4 py-2.5 text-gray-500 w-36">Client</td>
                        <td class="px-4 py-2.5 font-medium">
                            @if($payment->client)
                                <a href="{{ route('clients.show', $payment->client) }}" class="text-blue-600 hover:underline">
                                    {{ $payment->client->company_name }}
                                </a>
                            @else —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 text-gray-500">Email</td>
                        <td class="px-4 py-2.5 text-gray-800">{{ $payment->client?->email ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 text-gray-500">Invoice #</td>
                        <td class="px-4 py-2.5 font-medium">
                            @if($payment->invoice)
                                <a
{{--                                    href="{{ route('admin.xero.invoices.show', $payment->invoice) }}" --}}
                                   class="text-blue-600 hover:underline">
                                    {{ $payment->xero_invoice_number }}
                                </a>
                            @else
                                {{ $payment->xero_invoice_number ?? '—' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 text-gray-500">Xero invoice ID</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-600 break-all">{{ $payment->xero_invoice_xero_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 text-gray-500">Amount</td>
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 text-gray-500">Tenant</td>
                        <td class="px-4 py-2.5 text-gray-800">{{ $payment->tenant?->tenant_name ?? '—' }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>

        {{-- ROW 2: Stripe full details --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-4">
            <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                    Stripe
                    @if($stripeCharge || $stripePaymentIntent)
                        <span class="text-xs font-normal text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Live</span>
                    @elseif($stripeError)
                        <span class="text-xs font-normal text-red-500 bg-red-50 px-2 py-0.5 rounded-full" title="{{ $stripeError }}">API error</span>
                    @else
                        <span class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Stored</span>
                    @endif
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                {{-- Payment Intent --}}
                <div>
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Payment Intent</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-3 py-2 text-gray-500 text-xs w-28">ID</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 break-all">
                                {{ $payment->gateway_payment_id ?? '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-gray-500 text-xs">Batch ID</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 break-all">
                                {{ $payment->gateway_batch_id ?? '—' }}
                            </td>
                        </tr>
                        @if($stripePaymentIntent)
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Status</td>
                                <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                            {{ $stripePaymentIntent->status === 'succeeded' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $stripePaymentIntent->status }}
                                        </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Amount</td>
                                <td class="px-3 py-2 text-xs text-gray-800 font-medium">
                                    {{ strtoupper($stripePaymentIntent->currency) }}
                                    {{ number_format($stripePaymentIntent->amount / 100, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Description</td>
                                <td class="px-3 py-2 text-xs text-gray-700">{{ $stripePaymentIntent->description ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Created</td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    {{ \Carbon\Carbon::createFromTimestamp($stripePaymentIntent->created)->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @if($stripePaymentIntent->metadata && count((array)$stripePaymentIntent->metadata))
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs align-top">Metadata</td>
                                    <td class="px-3 py-2 text-xs text-gray-700">
                                        @foreach((array)$stripePaymentIntent->metadata as $k => $v)
                                            <div><span class="text-gray-400">{{ $k }}:</span> {{ $v }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        @endif
                        </tbody>
                    </table>
                </div>

                {{-- Charge + Bank account details --}}
                <div>
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Charge & bank account</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                        @if($stripeCharge)
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs w-28">Charge ID</td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ $stripeCharge->id }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Status</td>
                                <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                            {{ $stripeCharge->status === 'succeeded' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $stripeCharge->status }}
                                        </span>
                                </td>
                            </tr>
                            @if($stripeCharge->payment_method_details?->au_becs_debit ?? null)
                                @php $becs = $stripeCharge->payment_method_details->au_becs_debit; @endphp
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">BSB</td>
                                    <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ $becs->bsb_number ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">Account</td>
                                    <td class="px-3 py-2 font-mono text-xs text-gray-700">•••• {{ $becs->last4 ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">Mandate</td>
                                    <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ $becs->mandate ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">Fingerprint</td>
                                    <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ $becs->fingerprint ?? '—' }}</td>
                                </tr>
                            @endif
                            @if($stripeCharge->billing_details ?? null)
                                @php $bd = $stripeCharge->billing_details; @endphp
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">Billing name</td>
                                    <td class="px-3 py-2 text-xs text-gray-700">{{ $bd->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">Billing email</td>
                                    <td class="px-3 py-2 text-xs text-gray-700">{{ $bd->email ?? '—' }}</td>
                                </tr>
                            @endif
                            @if($stripeCharge->failure_code)
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs">Failure</td>
                                    <td class="px-3 py-2 text-xs text-red-600">
                                        {{ $stripeCharge->failure_code }} — {{ $stripeCharge->failure_message }}
                                    </td>
                                </tr>
                            @endif
                        @else
                            <tr>
                                <td colspan="2" class="px-3 py-4 text-xs text-gray-400 text-center">
                                    {{ $stripeError ? 'Could not load: ' . Str::limit($stripeError, 60) : 'No charge data yet.' }}
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                {{-- Balance transaction --}}
                <div>
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Balance transaction</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                        @if($stripeBalanceTx)
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs w-28">TX ID</td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-700 break-all">{{ $stripeBalanceTx->id }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Gross</td>
                                <td class="px-3 py-2 text-xs font-medium text-gray-800">
                                    {{ strtoupper($stripeBalanceTx->currency) }}
                                    {{ number_format($stripeBalanceTx->amount / 100, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Fee</td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    {{ strtoupper($stripeBalanceTx->currency) }}
                                    {{ number_format($stripeBalanceTx->fee / 100, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Net</td>
                                <td class="px-3 py-2 text-xs font-medium text-green-700">
                                    {{ strtoupper($stripeBalanceTx->currency) }}
                                    {{ number_format($stripeBalanceTx->net / 100, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Status</td>
                                <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                            {{ $stripeBalanceTx->status === 'available' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $stripeBalanceTx->status }}
                                        </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Available on</td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    {{ \Carbon\Carbon::createFromTimestamp($stripeBalanceTx->available_on)->format('d/m/Y') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Type</td>
                                <td class="px-3 py-2 text-xs text-gray-700">{{ $stripeBalanceTx->type ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Reporting cat.</td>
                                <td class="px-3 py-2 text-xs text-gray-700">{{ $stripeBalanceTx->reporting_category ?? '—' }}</td>
                            </tr>
                            @if($stripeBalanceTx->fee_details && count($stripeBalanceTx->fee_details))
                                <tr>
                                    <td class="px-3 py-2 text-gray-500 text-xs align-top">Fee breakdown</td>
                                    <td class="px-3 py-2 text-xs text-gray-700">
                                        @foreach($stripeBalanceTx->fee_details as $fd)
                                            <div>{{ $fd->description }}: {{ number_format($fd->amount / 100, 2) }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        @else
                            {{-- Fallback to stored --}}
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs w-28">TX ID</td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-700 break-all">
                                    {{ $payment->stripe_balance_transaction_id ?? '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Gross</td>
                                <td class="px-3 py-2 text-xs font-medium text-gray-800">
                                    {{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Fee</td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    {{ $payment->stripe_fee ? $payment->currency_code . ' ' . number_format($payment->stripe_fee, 2) : '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Net</td>
                                <td class="px-3 py-2 text-xs font-medium text-green-700">
                                    {{ $payment->stripe_net ? $payment->currency_code . ' ' . number_format($payment->stripe_net, 2) : '—' }}
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- ROW 3: Xero full details --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-4">
            <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                    Xero
                    @if($xeroPaymentData || $xeroInvoiceData)
                        <span class="text-xs font-normal text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Live</span>
                    @elseif($xeroError)
                        <span class="text-xs font-normal text-red-500 bg-red-50 px-2 py-0.5 rounded-full" title="{{ $xeroError }}">API error</span>
                    @else
                        <span class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Stored</span>
                    @endif
                </h3>
                @if($payment->xero_payment_id)
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full font-medium">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                        Posted
                    </span>
                @elseif($payment->xero_post_attempted && $payment->xero_post_error)
                    <span class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full font-medium">✕ Post failed</span>
                @elseif($payment->status === 'settled')
                    <span class="px-2 py-1 text-xs bg-orange-100 text-orange-600 rounded-full font-medium">Awaiting post</span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                {{-- Xero payment record --}}
                <div>
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Payment record</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-3 py-2 text-gray-500 text-xs w-32">Payment ID</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 break-all">{{ $payment->xero_payment_id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-gray-500 text-xs">Bank account</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 break-all">{{ $payment->xero_bank_account_id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-gray-500 text-xs">Posted at</td>
                            <td class="px-3 py-2 text-xs text-gray-700">{{ $payment->xero_payment_posted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-gray-500 text-xs">Post attempted</td>
                            <td class="px-3 py-2 text-xs text-gray-700">{{ $payment->xero_post_attempted ? 'Yes' : 'No' }}</td>
                        </tr>
                        @if($payment->xero_post_error)
                            <tr>
                                <td class="px-3 py-2 text-gray-500 text-xs">Post error</td>
                                <td class="px-3 py-2 text-xs text-red-600">{{ $payment->xero_post_error }}</td>
                            </tr>
                        @endif

                        @if($xeroPaymentData)
                            @php $xp = is_array($xeroPaymentData) ? $xeroPaymentData : (array)$xeroPaymentData; @endphp
                            <tr><td colspan="2" class="px-3 py-1.5 bg-green-50 text-xs text-green-700 font-medium">Live from Xero API</td></tr>
                            @foreach([
                                ['Amount',       isset($xp['Amount']) ? number_format($xp['Amount'], 2) : null],
                                ['Date',         $xp['Date'] ?? null],
                                ['Status',       $xp['Status'] ?? null],
                                ['Reference',    $xp['Reference'] ?? null],
                                ['Currency rate',$xp['CurrencyRate'] ?? null],
                                ['Is reconciled',$xp['IsReconciled'] ?? null],
                                ['Payment type', $xp['PaymentType'] ?? null],
                            ] as [$lbl, $val])
                                @if($val !== null)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-500 text-xs">{{ $lbl }}</td>
                                        <td class="px-3 py-2 text-xs text-gray-800">{{ is_bool($val) ? ($val ? 'Yes' : 'No') : $val }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>

                {{-- Xero invoice --}}
                <div>
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Invoice from Xero</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                        @if($xeroInvoiceData)
                            @php $xi = is_array($xeroInvoiceData) ? $xeroInvoiceData : (array)$xeroInvoiceData; @endphp
                            @foreach([
                                ['Invoice #',    $xi['InvoiceNumber'] ?? null],
                                ['Status',       $xi['Status'] ?? null],
                                ['Contact',      $xi['Contact']['Name'] ?? null],
                                ['Due date',     $xi['DueDate'] ?? null],
                                ['Amount due',   isset($xi['AmountDue']) ? number_format($xi['AmountDue'], 2) : null],
                                ['Amount paid',  isset($xi['AmountPaid']) ? number_format($xi['AmountPaid'], 2) : null],
                                ['Amount credited', isset($xi['AmountCredited']) ? number_format($xi['AmountCredited'], 2) : null],
                                ['Subtotal',     isset($xi['SubTotal']) ? number_format($xi['SubTotal'], 2) : null],
                                ['Total tax',    isset($xi['TotalTax']) ? number_format($xi['TotalTax'], 2) : null],
                                ['Total',        isset($xi['Total']) ? number_format($xi['Total'], 2) : null],
                                ['Currency',     $xi['CurrencyCode'] ?? null],
                                ['Reference',    $xi['Reference'] ?? null],
                                ['Sent to contact', isset($xi['SentToContact']) ? ($xi['SentToContact'] ? 'Yes' : 'No') : null],
                            ] as [$lbl, $val])
                                @if($val !== null)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-500 text-xs w-32">{{ $lbl }}</td>
                                        <td class="px-3 py-2 text-xs
                                                {{ $lbl === 'Status' && ($val === 'PAID') ? 'text-green-700 font-medium' : 'text-gray-800' }}">
                                            {{ $val }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            <tr>
                                <td colspan="2" class="px-3 py-4 text-xs text-center text-gray-400">
                                    @if($xeroError)
                                        Could not load: {{ Str::limit($xeroError, 80) }}
                                    @elseif(!$payment->xero_payment_id)
                                        Not posted to Xero yet.
                                    @else
                                        No invoice data returned.
                                    @endif
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- RETRY CHAIN --}}
        @if($payment->retryOf || $payment->retries->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-4">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-700 text-sm">Retry chain</h3>
                </div>
                <div class="p-4 flex flex-wrap gap-3 items-center text-sm">
                    @if($payment->retryOf)
                        <a href="{{ route('admin.directDebitPayment.show', $payment->retryOf) }}"
                           class="px-3 py-1.5 bg-gray-100 rounded text-gray-600 hover:bg-gray-200">
                            ← Original #{{ $payment->retryOf->our_reference }}
                        </a>
                        <span class="text-gray-300">→</span>
                    @endif
                    <span class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded font-medium">
                        This (Attempt #{{ $payment->attempt_number }})
                    </span>
                    @foreach($payment->retries as $retry)
                        <span class="text-gray-300">→</span>
                        <a href="{{ route('admin.directDebitPayment.show', $retry) }}"
                           class="px-3 py-1.5 bg-gray-100 rounded text-gray-600 hover:bg-gray-200">
                            Retry #{{ $retry->attempt_number }} — {{ ucfirst($retry->status) }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- TIMELINE --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h3 class="font-semibold text-gray-700 text-sm">Timeline</h3>
            </div>
            <div class="p-4">
                @php
                    $timeline = collect([
                        ['Initiated',            $payment->initiated_at,            'bg-gray-400'],
                        ['Submitted to gateway', $payment->submitted_to_gateway_at, 'bg-blue-400'],
                        ['Settled',              $payment->settled_at,              'bg-green-500'],
                        ['Posted to Xero',       $payment->xero_payment_posted_at,  'bg-teal-500'],
                        ['Failed',               $payment->failed_at,               'bg-red-500'],
                        ['Cancelled',            $payment->cancelled_at,            'bg-gray-400'],
                    ])->filter(fn($e) => $e[1] !== null)->sortBy(fn($e) => $e[1]);
                @endphp
                <div class="flex flex-col gap-3">
                    @forelse($timeline as [$label, $time, $dot])
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $dot }}"></div>
                            <div class="text-sm text-gray-700 font-medium w-44">{{ $label }}</div>
                            <div class="text-sm text-gray-500">{{ $time->format('d/m/Y H:i:s') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No timeline events yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelector('.cancel-btn')?.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                icon:               'warning',
                title:              'Cancel this payment?',
                text:               'This will mark the payment as cancelled.',
                showCancelButton:   true,
                confirmButtonText:  'Yes, cancel it',
                cancelButtonText:   'Keep it',
                confirmButtonColor: '#dc2626',
                cancelButtonColor:  '#6b7280',
            }).then(({ isConfirmed }) => { if (isConfirmed) form.submit(); });
        });
    </script>

</x-app-layout>

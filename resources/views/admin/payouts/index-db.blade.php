<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Stripe Payouts
        </h2>
    </x-slot>

    <form method="GET" action="{{ route('admin.payouts.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <select name="stripe_account" onchange="this.form.submit()"
                class="py-2 pl-3 pr-8 border border-gray-200 rounded-lg text-sm bg-white dark:bg-gray-800 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="all" @selected($selectedAccount === 'all')>All accounts</option>
            <option value="unassigned" @selected($selectedAccount === 'unassigned')>Unassigned (legacy)</option>
            @foreach($stripeAccounts as $stripeAccount)
                <option value="{{ $stripeAccount->id }}" @selected($selectedAccount instanceof \App\Models\StripeAccount && $selectedAccount->id === $stripeAccount->id)>
                    {{ $stripeAccount->display_name }}{{ $stripeAccount->company ? ' — '.$stripeAccount->company->name : '' }}
                </option>
            @endforeach
        </select>
        @if($selectedAccount instanceof \App\Models\StripeAccount)
            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                Showing {{ $selectedAccount->display_name }}{{ $selectedAccount->company ? ' — '.$selectedAccount->company->name : '' }}
            </span>
        @endif
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                <thead class="bg-gray-100 dark:bg-gray-700 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Payout
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Amount
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Account
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                        Gross
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                        Fees
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                        Net
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Batch Items
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Status
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Method
                    </th>
                    <th></th>
                </tr>
                </thead>


                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                @forelse($payouts as $payout)

                    @php
                        $status = match($payout->status) {
                            'paid' => [
                                'bg'=>'bg-green-100',
                                'text'=>'text-green-700',
                                'label'=>'Paid'
                            ],
                            'pending' => [
                                'bg'=>'bg-yellow-100',
                                'text'=>'text-yellow-700',
                                'label'=>'Pending'
                            ],
                            'in_transit' => [
                                'bg'=>'bg-blue-100',
                                'text'=>'text-blue-700',
                                'label'=>'In Transit'
                            ],
                            'failed' => [
                                'bg'=>'bg-red-100',
                                'text'=>'text-red-700',
                                'label'=>'Failed'
                            ],
                            default => [
                                'bg'=>'bg-gray-100',
                                'text'=>'text-gray-700',
                                'label'=>ucfirst($payout->status ?? 'unknown')
                            ]
                        };
                    @endphp


                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                        {{-- PAYOUT --}}
                        <td class="px-4 py-4 whitespace-nowrap align-top">

                            <div class="font-medium">
                                {{ optional($payout->stripe_created_at)->format('d M Y') ?? '—' }}
                            </div>

                            @if($payout->arrival_at)
                                <div class="text-xs text-gray-500 mt-1">
                                    Arrives {{ $payout->arrival_at->format('d M Y') }}
                                </div>
                            @endif

                        </td>



                        {{-- AMOUNT --}}
                        <td class="px-4 py-4 align-top">

                            <div class="font-bold text-lg">

                                {{ strtoupper($payout->currency) }}

                                {{ number_format($payout->amount / 100, 2) }}

                            </div>

                            <div class="text-xs text-gray-500">
                                {{ ucfirst($payout->type ?? 'standard') }} payout
                            </div>

                            <div class="text-xs text-gray-500 mt-1">
                                {{ $payout->charges_count ?? 0 }} charge{{ ($payout->charges_count ?? 0) === 1 ? '' : 's' }}
                                @if(isset($payout->app_count) || isset($payout->external_count))
                                    · {{ $payout->app_count ?? 0 }} app / {{ $payout->external_count ?? 0 }} ext
                                @endif
                            </div>

                        </td>

                        {{-- ACCOUNT --}}
                        <td class="px-4 py-4 align-top">
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $payout->stripeAccount?->display_name ?? 'Legacy pool' }}
                            </span>
                            @if($payout->stripeAccount?->company)
                                <div class="mt-1 text-[11px] text-gray-400">
                                    {{ $payout->stripeAccount->company->name }}
                                </div>
                            @endif
                        </td>



                        {{-- GROSS / FEES / NET (charge lines only, excl. payout debit) --}}
                        <td class="px-4 py-4 text-right whitespace-nowrap align-top">
                            <div class="font-medium tabular-nums">
                                {{ strtoupper($payout->currency) }}
                                {{ number_format(($payout->charges_gross ?? 0) / 100, 2) }}
                            </div>
                        </td>

                        <td class="px-4 py-4 text-right whitespace-nowrap align-top">
                            @if(($payout->charges_fees ?? 0) > 0)
                                <span class="text-red-600 tabular-nums">
                                    -{{ strtoupper($payout->currency) }}
                                    {{ number_format($payout->charges_fees / 100, 2) }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-right whitespace-nowrap align-top">
                            <div class="font-semibold tabular-nums">
                                {{ strtoupper($payout->currency) }}
                                {{ number_format(($payout->charges_net ?? 0) / 100, 2) }}
                            </div>
                        </td>

                        {{-- BATCH ITEMS (stripe_charge_batch_items with gross/fee/net) --}}
                        <td class="px-4 py-4 align-top">
                            @php
                                $batchItems = ($itemsMap[$payout->id] ?? collect());
                            @endphp
                            @if($batchItems->isNotEmpty())
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                    {{ $batchItems->count() }} item{{ $batchItems->count() === 1 ? '' : 's' }}
                                </span>
{{--                                <div class="mt-2 text-xs tabular-nums text-gray-600">--}}
{{--                                    <div>G: {{ strtoupper($payout->currency) }} {{ number_format(($payout->items_gross ?? 0) / 100, 2) }}</div>--}}
{{--                                    <div class="text-red-600">F: -{{ strtoupper($payout->currency) }} {{ number_format(($payout->items_fees ?? 0) / 100, 2) }}</div>--}}
{{--                                    <div class="font-semibold text-gray-900">N: {{ strtoupper($payout->currency) }} {{ number_format(($payout->items_net ?? 0) / 100, 2) }}</div>--}}
{{--                                </div>--}}
{{--                                <div class="mt-2 space-y-1">--}}
{{--                                    @foreach($batchItems->take(3) as $item)--}}
{{--                                        <div class="text-xs">--}}
{{--                                            @if($item->batch)--}}
{{--                                                <a href="{{ route('admin.stripe.batches.show', $item->batch) }}"--}}
{{--                                                   class="font-mono text-indigo-600 hover:text-indigo-800 hover:underline"--}}
{{--                                                   title="Item #{{ $item->id }} · {{ $item->status }} · G {{ number_format(($item->gross_amount ?? 0) / 100, 2) }} / F {{ number_format(($item->fee_amount ?? 0) / 100, 2) }} / N {{ number_format(($item->net_amount ?? 0) / 100, 2) }}">--}}
{{--                                                    {{ $item->batch->reference }} #{{ $item->id }}--}}
{{--                                                </a>--}}
{{--                                            @else--}}
{{--                                                <span class="font-mono text-gray-500">Item #{{ $item->id }}</span>--}}
{{--                                            @endif--}}
{{--                                            <span class="text-gray-400">· {{ $item->status }}</span>--}}
{{--                                        </div>--}}
{{--                                    @endforeach--}}
{{--                                    @if($batchItems->count() > 3)--}}
{{--                                        <div class="text-xs text-gray-400">--}}
{{--                                            +{{ $batchItems->count() - 3 }} more--}}
{{--                                        </div>--}}
{{--                                    @endif--}}
{{--                                </div>--}}
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-500/20">
                                    No items
                                </span>
                                <div class="mt-1 text-xs text-gray-400">
                                    invoice / external
                                </div>
                            @endif
                        </td>

                        {{-- STATUS --}}
                        <td class="px-4 py-4 align-top">

                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $status['bg'] }} {{ $status['text'] }}">
                            {{ $status['label'] }}
                        </span>


                            @if($payout->failure_message)

                                <div class="mt-2 text-xs text-red-600 max-w-xs">
                                    {{ $payout->failure_message }}
                                </div>

                            @endif

                        </td>



                        {{-- METHOD (incl. destination + reference) --}}
                        <td class="px-4 py-4 align-top">

                            <div class="font-medium">
                                {{ ucfirst($payout->method ?? '-') }}
                            </div>


                            @if($payout->statement_descriptor)

                                <div class="text-xs text-gray-500">
                                    {{ $payout->statement_descriptor }}
                                </div>

                            @endif

                            @if($payout->destination)

                                <div class="mt-1 font-mono text-xs text-gray-600 dark:text-gray-300" title="Destination">
                                    → {{ $payout->destination }}
                                </div>

                            @endif

                            <div class="mt-1 font-mono text-xs text-gray-400" title="{{ $payout->stripe_payout_id }}">
                                {{ $payout->stripe_payout_id }}
                            </div>

                        </td>



                        {{-- ACTION --}}
                        <td class="px-4 py-4 text-right align-top">

                            <a href="{{ route('admin.payouts.show', $payout->stripe_payout_id) }}"
                               class="inline-flex items-center px-3 py-2 text-sm bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100">

                                View

                            </a>

                        </td>

                    </tr>


                @empty

                    <tr>
                        <td colspan="10" class="py-10 text-center text-gray-500">
                            No payouts found.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
            <div class="px-5 py-4">
                {{ $payouts->links() }}
            </div>
        </div>
    </div>

</x-app-layout>

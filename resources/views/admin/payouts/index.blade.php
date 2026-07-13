<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Stripe Payouts
        </h2>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                <thead class="bg-gray-100 dark:bg-gray-700 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Date
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Amount
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Status
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Method
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Destination
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                        Reference
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
                                'label'=>ucfirst($payout->status)
                            ]
                        };
                    @endphp


                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                        {{-- DATE --}}
                        <td class="px-5 py-4">

                            <div class="font-medium">
                                {{ \Carbon\Carbon::createFromTimestamp($payout->created)->format('d M Y') }}
                            </div>

                            <div class="text-xs text-gray-500">
                                Created
                            </div>

                            @if($payout->arrival_date)
                                <div class="text-xs text-gray-500 mt-1">
                                    Arrives:
                                    {{ \Carbon\Carbon::createFromTimestamp($payout->arrival_date)->format('d M Y') }}
                                </div>
                            @endif

                        </td>



                        {{-- AMOUNT --}}
                        <td class="px-5 py-4">

                            <div class="font-bold text-lg">

                                {{ strtoupper($payout->currency) }}

                                {{ number_format($payout->amount / 100, 2) }}

                            </div>

                            <div class="text-xs text-gray-500">
                                {{ ucfirst($payout->type ?? 'standard') }} payout
                            </div>

                        </td>



                        {{-- STATUS --}}
                        <td class="px-5 py-4">

                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $status['bg'] }} {{ $status['text'] }}">
                            {{ $status['label'] }}
                        </span>


                            @if($payout->failure_message)

                                <div class="mt-2 text-xs text-red-600 max-w-xs">
                                    {{ $payout->failure_message }}
                                </div>

                            @endif

                        </td>



                        {{-- METHOD --}}
                        <td class="px-5 py-4">

                            <div class="font-medium">
                                {{ ucfirst($payout->method ?? '-') }}
                            </div>


                            @if($payout->statement_descriptor)

                                <div class="text-xs text-gray-500">
                                    {{ $payout->statement_descriptor }}
                                </div>

                            @endif

                        </td>



                        {{-- DESTINATION --}}
                        <td class="px-5 py-4">

                            @if($payout->destination)

                                <div class="font-mono text-xs">
                                    {{ $payout->destination }}
                                </div>

                            @else

                                <span class="text-gray-400">
                                -
                            </span>

                            @endif

                        </td>



                        {{-- REFERENCE --}}
                        <td class="px-5 py-4">

                            <div class="font-mono text-xs text-gray-500">
                                {{ $payout->id }}
                            </div>

                        </td>



                        {{-- ACTION --}}
                        <td class="px-5 py-4 text-right">

                            <a href="{{ route('admin.payouts.show', $payout->id) }}"
                               class="inline-flex items-center px-3 py-2 text-sm bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100">

                                View

                            </a>

                        </td>

                    </tr>


                @empty

                    <tr>
                        <td colspan="7" class="py-10 text-center text-gray-500">
                            No payouts found.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
            <div class="flex justify-between items-center mt-5">

                <div>
                    @if(request()->filled('starting_after'))
                        <a href="{{ route('admin.payouts.index', [
                'ending_before' => $firstId
            ]) }}"
                           class="px-4 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">
                            ← Previous
                        </a>
                    @endif
                </div>


                <div>
                    @if($hasMore)

                        <a href="{{ route('admin.payouts.index', [
                'starting_after' => $lastId
            ]) }}"
                           class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                            Next →
                        </a>

                    @endif
                </div>

            </div>
        </div>
    </div>

</x-app-layout>


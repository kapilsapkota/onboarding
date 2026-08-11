<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Stripe Transaction
        </h2>

    </x-slot>


    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        <div class="mb-5">

            <a
                href="{{ route('admin.stripe.transactions.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700"
            >
                ← Back to transactions
            </a>

        </div>


        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">

            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">

                <div class="flex justify-between items-start gap-5">

                    <div>

                        <h3 class="text-lg font-semibold">

                            {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}

                        </h3>

                        <div class="mt-1 text-sm font-mono text-gray-500">

                            {{ $transaction->id }}

                        </div>

                    </div>


                    <div class="text-right">

                        <div class="text-2xl font-bold">

                            {{ strtoupper($transaction->currency) }}

                            {{ number_format(($transaction->net ?? 0) / 100, 2) }}

                        </div>

                        <div class="text-sm text-gray-500">
                            Net
                        </div>

                    </div>

                </div>

            </div>


            <div class="p-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">


                    <div>

                        <div class="text-sm text-gray-500">
                            Gross
                        </div>

                        <div class="mt-1 font-semibold">

                            {{ strtoupper($transaction->currency) }}

                            {{ number_format(($transaction->amount ?? 0) / 100, 2) }}

                        </div>

                    </div>


                    <div>

                        <div class="text-sm text-gray-500">
                            Fee
                        </div>

                        <div class="mt-1 font-semibold text-red-600">

                            -{{ strtoupper($transaction->currency) }}

                            {{ number_format(($transaction->fee ?? 0) / 100, 2) }}

                        </div>

                    </div>


                    <div>

                        <div class="text-sm text-gray-500">
                            Net
                        </div>

                        <div class="mt-1 font-semibold">

                            {{ strtoupper($transaction->currency) }}

                            {{ number_format(($transaction->net ?? 0) / 100, 2) }}

                        </div>

                    </div>


                    <div>

                        <div class="text-sm text-gray-500">
                            Created
                        </div>

                        <div class="mt-1">

                            {{ \Carbon\Carbon::createFromTimestamp($transaction->created)->format('d/m/Y H:i:s') }}

                        </div>

                    </div>


                    <div>

                        <div class="text-sm text-gray-500">
                            Available On
                        </div>

                        <div class="mt-1">

                            @if($transaction->available_on)

                                {{ \Carbon\Carbon::createFromTimestamp($transaction->available_on)->format('d/m/Y') }}

                            @else

                                —

                            @endif

                        </div>

                    </div>


                    <div>

                        <div class="text-sm text-gray-500">
                            Description
                        </div>

                        <div class="mt-1">

                            {{ $transaction->description ?? '—' }}

                        </div>

                    </div>

                </div>


                @if($transaction->source)

                    <div class="mt-8">

                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Source
                        </div>

                        <pre class="mt-2 bg-black text-green-400 rounded-lg p-4 text-xs overflow-auto">{{ json_encode($transaction->source, JSON_PRETTY_PRINT) }}</pre>

                    </div>

                @endif


                <div class="mt-8">

                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Stripe Object
                    </div>

                    <pre class="mt-2 bg-black text-green-400 rounded-lg p-4 text-xs overflow-auto">{{ json_encode($transaction, JSON_PRETTY_PRINT) }}</pre>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>

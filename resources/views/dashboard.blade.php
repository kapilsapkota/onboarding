<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">

                <a href="{{ route('clients.index') }}"
                   class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition">
                    <div class="text-sm text-gray-500">Total Clients</div>
                    <div class="mt-2 text-4xl font-bold text-indigo-600">
                        {{ number_format($totalClients) }}
                    </div>
                </a>

                <a href="{{ route('admin.xero.index') }}"
                   class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition">
                    <div class="text-sm text-gray-500">Xero Invoices</div>
                    <div class="mt-2 text-4xl font-bold text-blue-600">
                        {{ number_format($totalInvoices) }}
                    </div>
                </a>

                <a href="{{ route('admin.directDebitPayment.index') }}"
                   class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition">
                    <div class="text-sm text-gray-500">Successful Direct Debits</div>
                    <div class="mt-2 text-4xl font-bold text-green-600">
                        {{ number_format($successfulDebits) }}
                    </div>
                </a>

                <a href="{{ route('admin.directDebitPayment.index') }}"
                   class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition">
                    <div class="text-sm text-gray-500">Failed Direct Debits</div>
                    <div class="mt-2 text-4xl font-bold text-red-600">
                        {{ number_format($failedDebits) }}
                    </div>
                </a>
            </div>

        </div>
    </div>


</x-app-layout>

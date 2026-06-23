<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 flex items-center justify-between">

                    <div>
                        <a href="{{ route('clients.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            Manage Clients
                        </a>
                    </div>

                    <div class="flex items-center gap-4">

                        {{-- XERO STATUS --}}
                        @if($xeroConnection = \App\Models\XeroConnection::first())
                            <span class="text-green-600 font-medium">
                            Connected to Xero
                        </span>

                            <form method="POST" action="{{ route('admin.xero.disconnect', $xeroConnection) }}">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                                    Disconnect
                                </button>
                            </form>
                        @else
                            <span class="text-yellow-500 font-medium">
                            Not connected
                        </span>

                            <a href="{{ route('admin.xero.connect') }}"
                               class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                Connect Xero
                            </a>
                        @endif

                    </div>

                </div>
            </div>

        </div>
    </div>


</x-app-layout>

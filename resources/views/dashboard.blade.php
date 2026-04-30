@php use App\Models\Client; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    {{-- Stats row --}}
    @php
        $total    = Client::count();
        $active   = Client::where('status','active')->count();
        $inactive = Client::where('status','inactive')->count();
        $contacts = \App\Models\ClientContact::count();
    @endphp

    <div class="py-6">
        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                @foreach([
                    ['label' => 'Total Clients',   'value' => $total,    'color' => 'yellow'],
                    ['label' => 'Active',           'value' => $active,   'color' => 'green'],
                    ['label' => 'Inactive',         'value' => $inactive, 'color' => 'gray'],
                    ['label' => 'Total Contacts',   'value' => $contacts, 'color' => 'blue'],
                ] as $stat)
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-5 border-l-4 border-{{ $stat['color'] }}-400 flex items-center gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ $stat['label'] }}</p>
                            <h3 class="text-2xl sm:text-3xl font-bold mt-0.5 text-gray-800 dark:text-gray-100">{{ number_format($stat['value']) }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="py-6">
        <div class="max-w-[95%] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <a href="{{ route('clients.index') }}"
                       class="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        Manage Clients
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

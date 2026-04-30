<x-app-layout>
    @section('title', 'Clients')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        .dataTables_filter {
            width: 50% !important;
        }
        .dataTables_filter input {
            width: 100% !important;
            max-width: 100% !important;
            padding: 8px 12px!important;
            border-radius: 8px!important;
            border: 1px solid #d1d5db!important;
        }

        .dataTables_length {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* 🔥 THIS is the key fix */
        .dataTables_length select {
            flex-shrink: 0;
            width: auto;
            min-width: 70px;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Clients
            </h2>
            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('onboarding.show') }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Onboarding Link
                </a>
                <a href="{{ route('clients.index') }}"
                   class="inline-flex px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                    All Clients
                </a>
            </div>
        </div>
    </x-slot>

    <x-message></x-message>

    {{-- Stats row --}}
    @php
        $total    = \App\Models\Client::count();
        $active   = \App\Models\Client::where('status','active')->count();
        $inactive = \App\Models\Client::where('status','inactive')->count();
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
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-5 border-l-4 border-{{ $stat['color'] }}-400 flex items-center gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ $stat['label'] }}</p>
                            <h3 class="text-2xl sm:text-3xl font-bold mt-0.5 text-gray-800 dark:text-gray-100">{{ number_format($stat['value']) }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filters --}}
{{--    <div class="pb-4">--}}
{{--        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">--}}
{{--            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">--}}
{{--                <form method="GET" action="{{ route('clients.index') }}" id="filter-form">--}}
{{--                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">--}}

{{--                        <div>--}}
{{--                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>--}}
{{--                            <div class="relative">--}}
{{--                                <input type="text" name="search" value="{{ request('search') }}"--}}
{{--                                       placeholder="Company, contact, email…"--}}
{{--                                       class="block w-full px-4 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200"--}}
{{--                                       oninput="debounceSubmit()">--}}
{{--                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">--}}
{{--                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>--}}
{{--                                    </svg>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div>--}}
{{--                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>--}}
{{--                            <select name="status" onchange="document.getElementById('filter-form').submit()"--}}
{{--                                    class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">--}}
{{--                                <option value="">All Status</option>--}}
{{--                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>--}}
{{--                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div>--}}
{{--                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Industry</label>--}}
{{--                            <select name="industry" onchange="document.getElementById('filter-form').submit()"--}}
{{--                                    class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">--}}
{{--                                <option value="">All Industries</option>--}}
{{--                                @foreach(['Hospitality & Food','Retail','Real Estate','Health & Wellness','Professional Services','Construction & Trades','Education','E-commerce','Events & Entertainment','Other'] as $ind)--}}
{{--                                    <option {{ request('industry') === $ind ? 'selected' : '' }}>{{ $ind }}</option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div>--}}
{{--                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>--}}
{{--                            <select name="sort" onchange="document.getElementById('filter-form').submit()"--}}
{{--                                    class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">--}}
{{--                                <option value="created_desc" {{ request('sort','created_desc') === 'created_desc' ? 'selected' : '' }}>Newest First</option>--}}
{{--                                <option value="created_asc"  {{ request('sort') === 'created_asc'  ? 'selected' : '' }}>Oldest First</option>--}}
{{--                                <option value="name_asc"     {{ request('sort') === 'name_asc'     ? 'selected' : '' }}>Name (A–Z)</option>--}}
{{--                                <option value="name_desc"    {{ request('sort') === 'name_desc'    ? 'selected' : '' }}>Name (Z–A)</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                    </div>--}}

{{--                    --}}{{-- Active filter badges --}}
{{--                    @if(request()->hasAny(['search','status','industry']))--}}
{{--                        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">--}}
{{--                            <span class="text-xs text-gray-500 dark:text-gray-400">Active:</span>--}}

{{--                            @if(request('search'))--}}
{{--                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">--}}
{{--                                    Search: <strong>{{ request('search') }}</strong>--}}
{{--                                    <a href="{{ request()->fullUrlWithoutQuery(['search','page']) }}" class="ml-1 hover:text-yellow-900">×</a>--}}
{{--                                </span>--}}
{{--                            @endif--}}
{{--                            @if(request('status'))--}}
{{--                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">--}}
{{--                                    Status: <strong>{{ ucfirst(request('status')) }}</strong>--}}
{{--                                    <a href="{{ request()->fullUrlWithoutQuery(['status','page']) }}" class="ml-1 hover:text-green-900">×</a>--}}
{{--                                </span>--}}
{{--                            @endif--}}
{{--                            @if(request('industry'))--}}
{{--                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">--}}
{{--                                    {{ request('industry') }}--}}
{{--                                    <a href="{{ request()->fullUrlWithoutQuery(['industry','page']) }}" class="ml-1 hover:text-blue-900">×</a>--}}
{{--                                </span>--}}
{{--                            @endif--}}

{{--                            <a href="{{ route('clients.index') }}" class="ml-auto text-xs text-red-600 dark:text-red-400 hover:text-red-800 font-medium">Clear All</a>--}}
{{--                        </div>--}}
{{--                    @endif--}}
{{--                </form>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

    {{-- Table --}}
    <div class="pb-8">
        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-10">

{{--            <div class="mb-3 flex flex-wrap gap-2 items-center justify-between">--}}
{{--                <p class="text-xs text-gray-600 dark:text-gray-400">--}}
{{--                    Showing {{ $clients->firstItem() ?? 0 }}–{{ $clients->lastItem() ?? 0 }} of {{ $clients->total() }} clients--}}
{{--                </p>--}}
{{--            </div>--}}

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto px-2 py-3">
                    <table class="min-w-full" id="clients-table">
                        <thead class="bg-gray-200 dark:bg-gray-700">
                        <tr class="text-left text-gray-600 dark:text-gray-400 uppercase text-xs">
                            <th class="px-4 py-3 font-semibold">Company</th>
                            <th class="px-4 py-3 font-semibold hidden sm:table-cell">Industry</th>
                            <th class="px-4 py-3 font-semibold">Primary Contact</th>
                            <th class="px-4 py-3 font-semibold">Direct Debit</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Email</th>
                            <th class="px-4 py-3 font-semibold hidden lg:table-cell">Phone</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Added</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

                        @foreach($clients as $client)
                            @php
                                $primary = $client->contacts->firstWhere('is_primary', true) ?? $client->contacts->first();
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">

                                {{-- Company --}}
                                <td class="px-4 py-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $client->company_name ?: '—' }}
                                    </div>
                                    @if($client->website)
                                        @php $websites = json_decode($client->website); @endphp
                                        @foreach($websites as $website)
                                            <div class="text-xs mt-0.5">
                                                <a href="{{ $website }}" target="_blank"
                                                   class="text-yellow-600 dark:text-yellow-400 hover:underline">
                                                    ↗ {{ parse_url($website, PHP_URL_HOST) ?? $website }}
                                                </a>
                                            </div>
                                        @endforeach
                                    @endif
                                </td>

                                {{-- Industry --}}
                                <td class="px-4 py-4 hidden sm:table-cell">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $client->industry ?: '—' }}
                                    </div>
                                </td>

                                {{-- Primary Contact --}}
                                <td class="px-4 py-4">
                                    @if($primary)
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $primary->full_name ?: '—' }}
                                        </div>
                                        @if($primary->role)
                                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $primary->role }}</div>
                                        @endif
                                        @if($client->contacts->count() > 1)
                                            <div class="text-xs mt-0.5">
                                                <span class="text-yellow-600 dark:text-yellow-400">+{{ $client->contacts->count() - 1 }} more</span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if($client->bsb && $client->account_number)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Added
                                        </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            Not Added
                                        </span>
                                     @endif
                                </td>

                                {{-- Email --}}
                                <td class="px-4 py-4 hidden md:table-cell">
                                    @if($primary?->email)
                                        <div class="flex items-center gap-1.5">
                                            <a href="mailto:{{ $primary->email }}"
                                               class="text-xs text-yellow-600 dark:text-yellow-400 hover:underline truncate max-w-[180px]">
                                                {{ $primary->email }}
                                            </a>
                                            <button onclick="copyText('{{ $primary->email }}', this)"
                                                    class="flex-shrink-0 text-gray-300 hover:text-yellow-500 transition"
                                                    title="Copy email">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                {{-- Phone --}}
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    @if($primary?->phone)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $primary->phone }}</span>
                                            <button onclick="copyText('{{ $primary->phone }}', this)"
                                                    class="flex-shrink-0 text-gray-300 hover:text-yellow-500 transition"
                                                    title="Copy phone">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-4">
                                    @if($client->status === 'active')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- Added --}}
                                <td class="px-4 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap hidden md:table-cell">
                                    {{ $client->created_at->format('M d, Y') }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-show-button href="{{ route('clients.show', $client) }}">View</x-show-button>
                                        <x-edit-button href="{{ route('clients.edit', $client) }}">Edit</x-edit-button>
                                        <x-delete-modal-button
                                            :model="$client"
                                            :action="route('clients.destroy', $client)"
                                            title="Delete Client"
                                            :display_name="$client->company_name ?? 'this client'"
                                        >Delete</x-delete-modal-button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
{{--                @if($clients->hasPages())--}}
{{--                    <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">--}}
{{--                        {{ $clients->appends(request()->query())->links() }}--}}
{{--                    </div>--}}
{{--                @endif--}}
            </div>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        let debounceTimer;
        function debounceSubmit() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => document.getElementById('filter-form').submit(), 600);
        }

        function copyText(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                btn.style.color = '#C9A84C';
                setTimeout(() => btn.style.color = '', 2000);
            });
        }
    </script>
    <script>
        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#clients-table')) {
                $('#clients-table').DataTable().destroy();
            }

            $.fn.dataTable.ext.errMode = 'none';

            $('#clients-table').DataTable({
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                scrollY: '50vh',
                scroller: true,
                lengthChange: true,
                paging: true,
                dom:
                    '<"flex flex-wrap items-center justify-center mb-4"f>' +
                    '<"flex flex-wrap items-center justify-between gap-3 mb-4"' +
                    '<"flex items-center gap-2"l>' +
                    '<"flex items-center gap-2"B>' +
                    '>' +
                    'ti',
                buttons: [
                    {
                        extend: 'copy',
                        text: `<span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>Copy</span>`,
                        className: 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-md bg-blue-600 text-white hover:bg-blue-500'
                    },
                    {
                        extend: 'csv',
                        text: `<span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 11V3m0 8l-3-3m3 3l3-3M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2h-3l-3 3-3-3H7a2 2 0 00-2 2v5a2 2 0 002 2z"/>
            </svg>CSV</span>`,
                        className: 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-md bg-gray-900 text-white hover:bg-gray-700'
                    },
                    {
                        extend: 'excel',
                        text: `<span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m1 8H8a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v9a2 2 0 01-2 2z"/>
            </svg>Excel</span>`,
                        className: 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-md bg-yellow-500 text-white hover:bg-yellow-600'
                    }
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Quick Filter..",
                    lengthMenu: "Showing _MENU_ clients per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ clients",
                    infoEmpty: "No clients available",
                    infoFiltered: "(filtered from _MAX_ total clients)",
                    zeroRecords: "No matching clients found",
                    emptyTable: `
            <div class="text-center py-10">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>

                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    No clients found
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Share the
                    <a href="{{ route('onboarding.show') }}" target="_blank" class="text-yellow-600 hover:underline">
                        onboarding link
                    </a>
                    or adjust your filters.
                </p>
            </div>
        `,
                    loadingRecords: "Loading...",
                    processing: "Processing...",

                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    },

                    buttons: {
                        csv: "Export CSV",
                        excel: "Export Excel"
                    }
                }
            });
        });
    </script>

</x-app-layout>

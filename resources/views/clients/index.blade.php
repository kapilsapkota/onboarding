<x-app-layout>
    @section('title', 'Clients')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6m-12 6a3 3 0 100-6"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            Clients
                        </h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Manage clients, contacts and integrations
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('onboarding.show') }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Onboarding Link
                </a>

                <a href="{{ route('clients.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    All Clients
                </a>
            </div>
        </div>
    </x-slot>

    <x-message/>

    @php
        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        |
        | These can later be moved into the controller for better separation
        | between presentation and database logic.
        |
        */
        $total    = \App\Models\Client::count();
        $active   = \App\Models\Client::where('status', 'active')->count();
        $inactive = \App\Models\Client::where('status', 'inactive')->count();
        $contacts = \App\Models\ClientContact::count();

        $sortUrl = function ($column) {
            $currentSort = request('sort', 'created_desc');

            $ascending = $column . '_asc';
            $descending = $column . '_desc';

            $nextSort = $currentSort === $ascending
                ? $descending
                : $ascending;

            return request()->fullUrlWithQuery([
                'sort' => $nextSort,
                'page' => 1,
            ]);
        };

        $sortIcon = function ($column) {
            $currentSort = request('sort', 'created_desc');

            if ($currentSort === $column . '_asc') {
                return '↑';
            }

            if ($currentSort === $column . '_desc') {
                return '↓';
            }

            return '↕';
        };
    @endphp

    <div class="space-y-4 pb-10">

        {{-- ============================================================
             STATS
        ============================================================= --}}
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">

                    {{-- Total --}}
                    <div
                        class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-yellow-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-yellow-700">

                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6m-12 6a3 3 0 100-6"/>
                                </svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Total Clients
                                </p>

                                <p class="mt-0.5 text-2xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white">
                                    {{ number_format($total) }}
                                </p>
                            </div>
                        </div>

                        <span class="hidden text-xs font-medium text-gray-400 sm:block dark:text-gray-500">
                    All clients
                </span>
                    </div>


                    {{-- Active --}}
                    <div
                        class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-green-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-700">

                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30">
                                <span class="h-3 w-3 rounded-full bg-green-500 shadow-sm shadow-green-500/30"></span>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Active
                                </p>

                                <p class="mt-0.5 text-2xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white">
                                    {{ number_format($active) }}
                                </p>
                            </div>
                        </div>

                        <span class="hidden text-xs font-medium text-green-600 sm:block dark:text-green-400">
                    Currently active
                </span>
                    </div>


                    {{-- Inactive --}}
                    <div
                        class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">

                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="1.8"
                                          d="M18 12H6"/>
                                </svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Inactive
                                </p>

                                <p class="mt-0.5 text-2xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white">
                                    {{ number_format($inactive) }}
                                </p>
                            </div>
                        </div>

                        <span class="hidden text-xs font-medium text-gray-400 sm:block dark:text-gray-500">
                    Inactive clients
                </span>
                    </div>


                    {{-- Contacts --}}
                    <div
                        class="group flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">

                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="1.8"
                                          d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm8-3a3 3 0 100 6m4 7v-2a4 4 0 00-3-3.87"/>
                                </svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Contacts
                                </p>

                                <p class="mt-0.5 text-2xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white">
                                    {{ number_format($contacts) }}
                                </p>
                            </div>
                        </div>

                        <span class="hidden text-xs font-medium text-blue-600 sm:block dark:text-blue-400">
                    Total contacts
                </span>
                    </div>

                </div>
            </div>
        </section>


        {{-- ============================================================
             FILTERS
        ============================================================= --}}
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">

                    <form method="GET"
                          action="{{ route('clients.index') }}"
                          id="filter-form">

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">

                            {{-- Search --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Search
                                </label>

                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-4 w-4 text-gray-400"
                                             fill="none"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="1.8"
                                                  d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>

                                    <input type="text"
                                           name="search"
                                           value="{{ request('search') }}"
                                           placeholder="Company, contact, email..."
                                           autocomplete="off"
                                           oninput="debounceSubmit()"
                                           class="block w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-yellow-400 focus:bg-white focus:ring-2 focus:ring-yellow-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-yellow-500 dark:focus:ring-yellow-900/30">
                                </div>
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Status
                                </label>

                                <select name="status"
                                        onchange="document.getElementById('filter-form').submit()"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-yellow-500">
                                    <option value="">All Status</option>
                                    <option value="active" @selected(request('status') === 'active')>
                                        Active
                                    </option>
                                    <option value="inactive" @selected(request('status') === 'inactive')>
                                        Inactive
                                    </option>
                                </select>
                            </div>

                            {{-- Industry --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Industry
                                </label>

                                <select name="industry"
                                        onchange="document.getElementById('filter-form').submit()"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-yellow-500">
                                    <option value="">All Industries</option>

                                    @foreach([
                                        'Hospitality & Food',
                                        'Retail',
                                        'Real Estate',
                                        'Health & Wellness',
                                        'Professional Services',
                                        'Construction & Trades',
                                        'Education',
                                        'E-commerce',
                                        'Events & Entertainment',
                                        'Other'
                                    ] as $ind)
                                        <option value="{{ $ind }}"
                                            @selected(request('industry') === $ind)>
                                            {{ $ind }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sort --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Sort By
                                </label>

                                <select name="sort"
                                        onchange="document.getElementById('filter-form').submit()"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-yellow-500">

                                    <option value="created_desc"
                                            @selected(request('sort', 'created_desc') === 'created_desc')}>
                                        Newest First
                                    </option>

                                    <option value="created_asc"
                                            @selected(request('sort') === 'created_asc')}>
                                        Oldest First
                                    </option>

                                    <option value="name_asc"
                                            @selected(request('sort') === 'name_asc')}>
                                        Name (A–Z)
                                    </option>

                                    <option value="name_desc"
                                            @selected(request('sort') === 'name_desc')}>
                                        Name (Z–A)
                                    </option>
                                </select>
                            </div>

                        </div>

                        {{-- Active filters --}}
                        @if(request()->hasAny(['search', 'status', 'industry']))
                            <div
                                class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">

                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Filters:
                        </span>

                                @if(request('search'))
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-yellow-200 bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-800 dark:border-yellow-900/50 dark:bg-yellow-900/20 dark:text-yellow-300">
                                <span>Search:</span>
                                <strong class="max-w-[180px] truncate">
                                    {{ request('search') }}
                                </strong>

                                <a href="{{ request()->fullUrlWithoutQuery(['search', 'page']) }}"
                                   class="ml-0.5 text-yellow-500 hover:text-yellow-800"
                                   aria-label="Remove search filter">
                                    ×
                                </a>
                            </span>
                                @endif

                                @if(request('status'))
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-800 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
                                <span>Status:</span>
                                <strong>{{ ucfirst(request('status')) }}</strong>

                                <a href="{{ request()->fullUrlWithoutQuery(['status', 'page']) }}"
                                   class="ml-0.5 text-green-500 hover:text-green-800"
                                   aria-label="Remove status filter">
                                    ×
                                </a>
                            </span>
                                @endif

                                @if(request('industry'))
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-800 dark:border-blue-900/50 dark:bg-blue-900/20 dark:text-blue-300">
                                <strong>{{ request('industry') }}</strong>

                                <a href="{{ request()->fullUrlWithoutQuery(['industry', 'page']) }}"
                                   class="ml-0.5 text-blue-500 hover:text-blue-800"
                                   aria-label="Remove industry filter">
                                    ×
                                </a>
                            </span>
                                @endif

                                <a href="{{ route('clients.index') }}"
                                   class="ml-auto text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    Clear all
                                </a>

                            </div>
                        @endif

                    </form>
                </div>
            </div>
        </section>


        {{-- ============================================================
             TABLE
        ============================================================= --}}
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">

                {{-- Table toolbar --}}
                <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    {{-- Per page --}}
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Show
                        </span>

                        <form method="GET"
                              action="{{ route('clients.index') }}"
                              class="flex items-center">

                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $arrayValue)
                                        <input type="hidden"
                                               name="{{ $key }}[]"
                                               value="{{ $arrayValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden"
                                           name="{{ $key }}"
                                           value="{{ $value }}">
                                @endif
                            @endforeach

                            <select name="per_page"
                                    onchange="this.form.submit()"
                                    class="rounded-lg border-gray-200 bg-white py-1.5 pl-2.5 pr-7 text-xs font-medium text-gray-700 shadow-sm focus:border-yellow-400 focus:ring-yellow-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">

                                <option value="25" @selected($perPage == 25)>25</option>
                                <option value="50" @selected($perPage == 50)>50</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                                <option value="250" @selected($perPage == 250)>250</option>
                                <option value="all" @selected($perPage === 'all')>All</option>

                            </select>
                        </form>

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            per page
                        </span>

                        <a href="{{ route('clients.export', request()->query()) }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2
                              text-xs font-semibold text-gray-700 shadow-sm transition
                              hover:border-gray-300 hover:bg-gray-50
                              dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200
                              dark:hover:border-gray-600 dark:hover:bg-gray-700">

                            <svg class="h-4 w-4 text-green-600 dark:text-green-400"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="1.8"
                                      d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14a2 2 0 002-2v-3M3 16v3a2 2 0 002 2"/>
                            </svg>

                            Export
                        </a>
                    </div>

                    <div>
                        @if($clients instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Showing
                                <strong class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ $clients->firstItem() ?? 0 }}
                                </strong>
                                –
                                <strong class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ $clients->lastItem() ?? 0 }}
                                </strong>
                                of
                                <strong class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ number_format($clients->total()) }}
                                </strong>
                                clients
                            </p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Showing
                                <strong class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ number_format($clients->count()) }}
                                </strong>
                                clients
                            </p>
                        @endif
                    </div>
                </div>


                {{-- Main table card --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm
                 dark:border-gray-700 dark:bg-gray-800">

                    <div class="overflow-x-auto">
                        <table class="min-w-full">

                            {{-- Header --}}
                            {{-- Header --}}
                            <thead
                                class="sticky top-0 z-10 border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <tr class="text-left">

                                {{-- Company --}}
                                <th class="whitespace-nowrap px-5 py-4">
                                    <a href="{{ $sortUrl('name') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Company</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 transition group-hover:bg-gray-200 group-hover:text-gray-600 dark:bg-gray-700 dark:text-gray-500 dark:group-hover:bg-gray-600 dark:group-hover:text-gray-300">
                    {{ $sortIcon('name') }}
                </span>
                                    </a>
                                </th>

                                {{-- Industry --}}
                                <th class="hidden whitespace-nowrap px-5 py-4 sm:table-cell">
                                    <a href="{{ $sortUrl('industry') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Industry</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                    {{ $sortIcon('industry') }}
                </span>
                                    </a>
                                </th>

                                {{-- Contact --}}
                                <th class="whitespace-nowrap px-5 py-4">
                                    <a href="{{ $sortUrl('contact') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Primary Contact</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('contact') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Email --}}
                                <th class="hidden whitespace-nowrap px-5 py-4 md:table-cell">
                                    <a href="{{ $sortUrl('email') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Email</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('email') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Phone --}}
                                <th class="hidden whitespace-nowrap px-5 py-4 lg:table-cell">
                                    <a href="{{ $sortUrl('phone') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Phone</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('phone') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Status --}}
                                <th class="whitespace-nowrap px-5 py-4">
                                    <a href="{{ $sortUrl('status') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Status</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('status') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Xero --}}
                                <th class="whitespace-nowrap px-5 py-4">
                                    <a href="{{ $sortUrl('xero') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Xero</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('xero') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Stripe --}}
                                <th class="whitespace-nowrap px-5 py-4">
                                    <a href="{{ $sortUrl('stripe') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Stripe</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('stripe') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Added --}}
                                <th class="hidden whitespace-nowrap px-5 py-4 md:table-cell">
                                    <a href="{{ $sortUrl('created') }}"
                                       class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <span>Added</span>
                                        <span
                                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-bold text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            {{ $sortIcon('created') }}
                                        </span>
                                    </a>
                                </th>

                                {{-- Actions --}}
                                <th class="whitespace-nowrap px-5 py-4 text-right">
            <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">
                Actions
            </span>
                                </th>

                            </tr>
                            </thead>


                            {{-- Body --}}
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">

                            @forelse($clients as $client)

                                @php
                                    $primary = $client->contacts->firstWhere('is_primary', true)
                                        ?? $client->contacts->first();

                                    $contactCount = $client->contacts->count();

                                    $hasCustomer = !empty($client->stripe_customer_id);
                                    $hasPaymentMethod = !empty($client->stripe_payment_method_id);

                                    $xeroCount = $client->xeroContacts->count();
                                @endphp

                                <tr class="group transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-750">

                                    {{-- Company --}}
                                    <td class="px-5 py-4 align-top">
                                        <div class="min-w-[190px]">

                                            <div class="flex items-start gap-3">

                                                <div
                                                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ strtoupper(substr($client->company_name ?: 'C', 0, 1)) }}
                                                </div>

                                                <div class="min-w-0">
                                                    <a href="{{ route('clients.show', $client) }}"
                                                       class="block truncate text-sm font-semibold text-gray-900 transition hover:text-yellow-600 dark:text-gray-100 dark:hover:text-yellow-400">
                                                        {{ $client->company_name ?: 'N/A' }}
                                                    </a>

                                                    @if($client->website)
                                                        @php
                                                            $websites = json_decode($client->website) ?: [];
                                                        @endphp

                                                        @foreach($websites as $website)
                                                            <a href="{{ $website }}"
                                                               target="_blank"
                                                               rel="noopener noreferrer"
                                                               class="mt-0.5 block max-w-[180px] truncate text-[11px] text-gray-400 transition hover:text-yellow-600 hover:underline dark:text-gray-500 dark:hover:text-yellow-400">
                                                                {{ parse_url($website, PHP_URL_HOST) ?? $website }}
                                                            </a>
                                                        @endforeach
                                                    @endif
                                                </div>

                                            </div>
                                        </div>
                                    </td>


                                    {{-- Industry --}}
                                    <td class="hidden px-5 py-4 align-top sm:table-cell">
                                        @if($client->industry)
                                            <span
                                                class="inline-flex max-w-[150px] truncate rounded-lg bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $client->industry }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>


                                    {{-- Primary Contact --}}
                                    <td class="px-5 py-4 align-top">
                                        @if($primary)
                                            <div class="min-w-[160px]">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $primary->full_name ?: 'N/A' }}
                                                </div>

                                                @if($primary->role)
                                                    <div class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">
                                                        {{ $primary->role }}
                                                    </div>
                                                @endif

                                                @if($contactCount > 1)
                                                    <div class="mt-1">
                                                        <span
                                                            class="text-[11px] font-medium text-yellow-600 dark:text-yellow-400">
                                                            +{{ $contactCount - 1 }} more
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">No contact</span>
                                        @endif
                                    </td>


                                    {{-- Email --}}
                                    <td class="hidden px-5 py-4 align-top md:table-cell">
                                        @if($primary?->email)

                                            <div class="flex max-w-[220px] items-center gap-2">
                                                <a href="mailto:{{ $primary->email }}"
                                                   class="truncate text-xs font-medium text-gray-600 transition hover:text-yellow-600 hover:underline dark:text-gray-300 dark:hover:text-yellow-400">
                                                    {{ $primary->email }}
                                                </a>

                                                <button type="button"
                                                        data-copy="{{ $primary->email }}"
                                                        onclick="copyText(this.dataset.copy, this)"
                                                        class="flex-shrink-0 rounded-md p-1 text-gray-300 transition hover:bg-gray-100 hover:text-yellow-500 dark:hover:bg-gray-700"
                                                        title="Copy email">

                                                    <svg class="h-3.5 w-3.5"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         viewBox="0 0 24 24">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="1.8"
                                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </button>
                                            </div>

                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>


                                    {{-- Phone --}}
                                    <td class="hidden px-5 py-4 align-top lg:table-cell">
                                        @if($primary?->phone)

                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                    {{ $primary->phone }}
                                                </span>

                                                <button type="button"
                                                        data-copy="{{ $primary->phone }}"
                                                        onclick="copyText(this.dataset.copy, this)"
                                                        class="flex-shrink-0 rounded-md p-1 text-gray-300 transition hover:bg-gray-100 hover:text-yellow-500 dark:hover:bg-gray-700"
                                                        title="Copy phone">

                                                    <svg class="h-3.5 w-3.5"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         viewBox="0 0 24 24">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="1.8"
                                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </button>
                                            </div>

                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>


                                    {{-- Status --}}
                                    <td class="px-5 py-4 align-top">
                                        @if($client->status === 'active')
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </td>


                                    {{-- Xero --}}
                                    <td class="px-5 py-4 align-top">
                                        @if($xeroCount > 0)

                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                                    <svg class="h-3.5 w-3.5"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         viewBox="0 0 24 24">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M5 12h14M12 5l7 7-7 7"/>
                                                    </svg>
                                                </span>

                                                <div>
                                                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                                        Connected
                                                    </div>

                                                    <div class="text-[10px] text-gray-400">
                                                        {{ $xeroCount }} {{ $xeroCount === 1 ? 'contact' : 'contacts' }}
                                                    </div>
                                                </div>
                                            </div>

                                        @else
                                            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                                Not connected
                                            </span>
                                        @endif
                                    </td>


                                    {{-- Stripe --}}
                                    <td class="px-5 py-4 align-top">

                                        @if($hasCustomer && $hasPaymentMethod)

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                                Connected
                                            </span>

                                        @elseif($hasCustomer && !$hasPaymentMethod)

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 px-2.5 py-1 text-[11px] font-semibold text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                                Customer only
                                            </span>

                                        @else

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                                Not connected
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Added --}}
                                    <td class="hidden whitespace-nowrap px-5 py-4 align-top md:table-cell">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $client->created_at->format('M d, Y') }}
                                        </span>
                                    </td>


                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right align-top">
                                        <div class="flex items-center justify-end gap-1">

                                            {{-- View --}}
                                            <a href="{{ route('clients.show', $client) }}"
                                               title="View client"
                                               aria-label="View client"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg
                  border border-gray-200 bg-white text-gray-500
                  transition-all duration-150
                  hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600
                  focus:outline-none focus:ring-2 focus:ring-blue-500/20
                  dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400
                  dark:hover:border-blue-800 dark:hover:bg-blue-900/20 dark:hover:text-blue-400">

                                                <svg class="h-4 w-4"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.8"
                                                          d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7z"/>
                                                    <circle cx="12"
                                                            cy="12"
                                                            r="2.75"
                                                            stroke-width="1.8"/>
                                                </svg>
                                            </a>


                                            {{-- Edit --}}
                                            <a href="{{ route('clients.edit', $client) }}"
                                               title="Edit client"
                                               aria-label="Edit client"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg
                  border border-gray-200 bg-white text-gray-500
                  transition-all duration-150
                  hover:border-yellow-200 hover:bg-yellow-50 hover:text-yellow-600
                  focus:outline-none focus:ring-2 focus:ring-yellow-500/20
                  dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400
                  dark:hover:border-yellow-800 dark:hover:bg-yellow-900/20 dark:hover:text-yellow-400">

                                                <svg class="h-4 w-4"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.8"
                                                          d="M16.862 3.487a2.1 2.1 0 013.05 2.89L8.4 17.89l-4.15.95.95-4.15L16.862 3.487z"/>
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.8"
                                                          d="M15.5 5l3.5 3.5"/>
                                                </svg>
                                            </a>


                                            {{-- Delete --}}
                                            <x-delete-modal-button
                                                :model="$client"
                                                :action="route('clients.destroy', $client)"
                                                title="Delete Client"
                                                :display_name="$client->company_name ?? 'this client'"
                                            >
                                            </x-delete-modal-button>

                                        </div>
                                    </td>


                                </tr>

                            @empty

                                <tr>
                                    <td colspan="10" class="px-6 py-20">

                                        <div class="mx-auto flex max-w-md flex-col items-center text-center">

                                            <div
                                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                                                <svg class="h-8 w-8"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="1.5"
                                                          d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6m-12 6a3 3 0 100-6"/>
                                                </svg>
                                            </div>

                                            <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                No clients found
                                            </h3>

                                            <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                                We couldn't find any clients matching your current filters.
                                                Try changing your search or filters.
                                            </p>

                                            <a href="{{ route('onboarding.show') }}"
                                               target="_blank"
                                               class="mt-5 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                                                Get Onboarding Link
                                            </a>

                                        </div>

                                    </td>
                                </tr>

                            @endforelse

                            </tbody>

                        </table>
                    </div>


                    {{-- Pagination --}}
                    @if($clients instanceof \Illuminate\Pagination\LengthAwarePaginator && $clients->hasPages())

                        <div
                            class="border-t border-gray-100 bg-gray-50/50 px-4 py-4 dark:border-gray-700 dark:bg-gray-900/30 sm:px-6">
                            {{ $clients->withQueryString()->links() }}
                        </div>

                    @endif

                </div>
            </div>
        </section>

    </div>


    {{-- ================================================================
         JAVASCRIPT
    ================================================================= --}}
    <script>
        let debounceTimer;

        function debounceSubmit() {
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                const form = document.getElementById('filter-form');

                let page = form.querySelector('input[name="page"]');

                if (!page) {
                    page = document.createElement('input');
                    page.type = 'hidden';
                    page.name = 'page';
                    form.appendChild(page);
                }

                page.value = '1';

                form.submit();
            }, 500);
        }


        async function copyText(text, btn) {
            try {
                await navigator.clipboard.writeText(text);

                const originalColor = btn.style.color;

                btn.style.color = '#C9A84C';

                const originalTitle = btn.getAttribute('title');
                btn.setAttribute('title', 'Copied!');

                setTimeout(() => {
                    btn.style.color = originalColor;
                    btn.setAttribute('title', originalTitle || 'Copy');
                }, 1500);

            } catch (error) {
                console.error('Could not copy text:', error);
            }
        }
    </script>

</x-app-layout>

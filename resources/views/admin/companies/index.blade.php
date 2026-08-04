<x-app-layout>
    @section('title', 'Companies')

    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Companies
            </h2>
            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('admin.companies.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Company
                </a>
            </div>
        </div>
    </x-slot>

    <x-message></x-message>

    {{-- Stats row --}}
    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-10">
            <div class="grid grid-cols-2 lg:grid-cols-2 gap-3 sm:gap-4 mb-6 max-w-sm">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-5 border-l-4 border-yellow-400 flex items-center gap-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">Total Companies</p>
                        <h3 class="text-2xl sm:text-3xl font-bold mt-0.5 text-gray-800 dark:text-gray-100">{{ number_format($total) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="pb-4">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-10">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
                <form method="GET" action="{{ route('admin.companies.index') }}" id="filter-form">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="Name, email, city…"
                                       class="block w-full px-4 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200"
                                       oninput="debounceSubmit()">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
                            <select name="sort" onchange="document.getElementById('filter-form').submit()"
                                    class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
                                <option value="created_desc" {{ request('sort', 'created_desc') === 'created_desc' ? 'selected' : '' }}>Newest First</option>
                                <option value="created_asc"  {{ request('sort') === 'created_asc'  ? 'selected' : '' }}>Oldest First</option>
                                <option value="name_asc"     {{ request('sort') === 'name_asc'     ? 'selected' : '' }}>Name (A–Z)</option>
                                <option value="name_desc"    {{ request('sort') === 'name_desc'    ? 'selected' : '' }}>Name (Z–A)</option>
                            </select>
                        </div>

                    </div>

                    {{-- Active filter badges --}}
                    @if(request()->hasAny(['search']))
                        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Active:</span>

                            @if(request('search'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                    Search: <strong>{{ request('search') }}</strong>
                                    <a href="{{ request()->fullUrlWithoutQuery(['search', 'page']) }}" class="ml-1 hover:text-yellow-900">×</a>
                                </span>
                            @endif

                            <a href="{{ route('admin.companies.index') }}" class="ml-auto text-xs text-red-600 dark:text-red-400 hover:text-red-800 font-medium">Clear All</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="pb-10">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-10">

            <div class="mb-3 flex flex-wrap gap-2 items-center justify-between">
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Showing {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }} companies
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-200 dark:bg-gray-700">
                        <tr class="text-left text-gray-600 dark:text-gray-400 uppercase text-xs">
                            <th class="px-4 py-3 font-semibold">Logo</th>
                            <th class="px-4 py-3 font-semibold">Name</th>
                            <th class="px-4 py-3 font-semibold hidden sm:table-cell">Slug</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Email</th>
                            <th class="px-4 py-3 font-semibold hidden lg:table-cell">Phone</th>
                            <th class="px-4 py-3 font-semibold hidden lg:table-cell">Location</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Added</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

                        @forelse($companies as $company)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">

                                {{-- Logo --}}
                                <td class="px-4 py-4">
                                    @if($company->logo)
                                        <img src="{{ asset('images/' . $company->logo) }}"
                                             alt="{{ $company->name }}"
                                             class="h-10 w-auto max-w-[80px] object-contain rounded">
                                    @else
                                        <div class="h-10 w-10 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                    @endif
                                </td>

                                {{-- Name --}}
                                <td class="px-4 py-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $company->name }}
                                    </div>
                                </td>

                                {{-- Slug --}}
                                <td class="px-4 py-4 hidden sm:table-cell">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $company->slug ?? '—' }}
                                    </span>
                                </td>

                                {{-- Email --}}
                                <td class="px-4 py-4 hidden md:table-cell">
                                    @if($company->email)
                                        <div class="flex items-center gap-1.5">
                                            <a href="mailto:{{ $company->email }}"
                                               class="text-xs text-yellow-600 dark:text-yellow-400 hover:underline truncate max-w-[180px]">
                                                {{ $company->email }}
                                            </a>
                                            <button onclick="copyText('{{ $company->email }}', this)"
                                                    class="flex-shrink-0 text-gray-300 hover:text-yellow-500 transition"
                                                    title="Copy email">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Phone --}}
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    @if($company->phone)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $company->phone }}</span>
                                            <button onclick="copyText('{{ $company->phone }}', this)"
                                                    class="flex-shrink-0 text-gray-300 hover:text-yellow-500 transition"
                                                    title="Copy phone">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Location --}}
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    @php
                                        $location = array_filter([$company->city, $company->state, $company->country]);
                                    @endphp
                                    @if($location)
                                        <span class="text-xs text-gray-600 dark:text-gray-300">{{ implode(', ', $location) }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Added --}}
                                <td class="px-4 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap hidden md:table-cell">
                                    {{ $company->created_at->format('M d, Y') }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-show-button href="{{ route('admin.companies.show', $company) }}">View</x-show-button>
                                        <x-edit-button href="{{ route('admin.companies.edit', $company) }}">Edit</x-edit-button>
                                        <x-delete-modal-button
                                            :model="$company"
                                            :action="route('admin.companies.destroy', $company)"
                                            title="Delete Company"
                                            :display_name="$company->name"
                                        >Delete</x-delete-modal-button>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No companies found</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            <a href="{{ route('admin.companies.create') }}" class="text-yellow-600 hover:underline">Add your first company</a>
                                            or adjust your filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($companies->hasPages())
                    <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $companies->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

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

</x-app-layout>

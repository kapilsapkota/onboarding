<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Categories
            </h2>
            <a href="{{ route('admin.categories.create') }}"
               class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Category
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap gap-3 items-center">
            <form method="GET" class="flex gap-2 flex-1 min-w-0">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by name or slug…"
                       class="flex-1 min-w-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <button type="submit"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Search
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.categories.index') }}"
                       class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
                @endif
            </form>

            {{-- Status tabs --}}
            <div class="flex gap-1 text-sm">
                @foreach(['' => 'All', '1' => 'Active', '0' => 'Inactive'] as $val => $label)
                    <a href="{{ route('admin.categories.index', array_merge(request()->except('status', 'page'), $val !== '' ? ['status' => $val] : [])) }}"
                       class="px-3 py-1.5 rounded-md font-medium transition
                              {{ request('status', '') === $val
                                  ? 'bg-blue-600 text-white'
                                  : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Category</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Slug</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-center">Products</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-center">Sort</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($category->icon)
                                    <x-image-preview
                                        src="{{ asset('storage/'.$category->icon) }}"
                                        alt="{{ $category->name }}"
                                        thumbClass="w-14 h-14"
                                        thumbImageClass="w-16 h-16 rounded-lg object-cover flex-shrink-0 bg-gray-100"
                                        previewClass="w-[36rem] h-[36rem]"
                                    />
{{--                                    <img src="{{ asset('storage/'.$category->icon) }}"--}}
{{--                                         alt="{{ $category->name }}"--}}
{{--                                         class="w-16 h-16 rounded-lg object-cover flex-shrink-0 bg-gray-100">--}}
                                @else
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.categories.show', $category) }}"
                                       class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                        {{ $category->name }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                            {{ $category->slug }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold
                                         {{ $category->products_count > 0
                                             ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                             : 'bg-gray-100 text-gray-400 dark:bg-gray-700' }}">
                                {{ $category->products_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 text-xs tabular-nums">
                            {{ $category->sort_order }}
                        </td>
                        <td class="px-4 py-3">
                            @if($category->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('admin.categories.show', $category) }}"
                                   class="text-gray-400 hover:text-blue-500 transition" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="text-gray-400 hover:text-yellow-500 transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                {{-- Duplicate --}}
                                <button type="button"
                                        onclick="openDuplicateModal('{{ $category->id }}','{{ addslashes($category->name) }}')"
                                        class="text-gray-400 hover:text-green-500 transition"
                                        title="Duplicate">

                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                <button type="button"
                                        onclick="openDeleteSimpleModal(
            '{{ route('admin.categories.destroy',$category) }}',
            '{{ $category->name }}'
        )"
                                        class="text-gray-400 hover:text-red-500 transition"
                                        title="Delete">

                                    <svg class="w-4 h-4"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>

                                    </svg>

                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            No categories found.
                            <a href="{{ route('admin.categories.create') }}" class="text-blue-500 hover:underline">Create your first category</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            @if($categories->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $categories->links() }}
                </div>
            @endif

            <x-modals.duplicate/>
            <x-modals.delete />
        </div>

    </div>
</x-app-layout>

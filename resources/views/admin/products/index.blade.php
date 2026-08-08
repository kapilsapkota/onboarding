<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Products
            </h2>
            <a href="{{ route('admin.products.create') }}"
               class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Product
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        {{-- Flash messages --}}
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
            <form id="searchForm" method="GET" class="flex gap-2 flex-1 min-w-0">
                <input type="text"
                       id="searchInput"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by name, category…"
                       class="flex-1 min-w-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <input type="hidden" name="status"    value="{{ request('status') }}">
                <input type="hidden" name="frequency" value="{{ request('frequency') }}">
                <input type="hidden" name="price_type" value="{{ request('price_type') }}">
                @if(request()->hasAny(['search', 'category', 'price_type', 'status', 'frequency']))
                    <a href="{{ route('admin.products.index') }}"
                       class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
                @endif
            </form>

            {{-- Category dropdown --}}
            <select onchange="window.location = this.value"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
                   text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                <option value="{{ route('admin.products.index', array_merge(request()->except('category', 'page'))) }}">
                    All Categories
                </option>
                @foreach($categories as $category)
                    <option value="{{ route('admin.products.index', array_merge(request()->except('category', 'page'), ['category' => $category->slug])) }}"
                        {{ request('category') === $category->slug ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            {{-- Status tabs --}}
            <select onchange="window.location = this.value"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
               text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">

                <option value="{{ route('admin.products.index', request()->except(['status', 'page'])) }}"
                    {{ request('status', '') === '' ? 'selected' : '' }}>
                    All Statuses
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['status', 'page']), ['status' => 1])) }}"
                    {{ request('status') === '1' ? 'selected' : '' }}>
                    Active
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['status', 'page']), ['status' => 0])) }}"
                    {{ request('status') === '0' ? 'selected' : '' }}>
                    Inactive
                </option>

            </select>

            <select onchange="window.location = this.value"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
               text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">

                <option value="{{ route('admin.products.index', request()->except(['quote_default', 'page'])) }}"
                    {{ request('quote_default', '') === '' ? 'selected' : '' }}>
                    Quote Default
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['quote_default', 'page']), ['quote_default' => 1])) }}"
                    {{ request('quote_default') === '1' ? 'selected' : '' }}>
                    Is Default
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['quote_default', 'page']), ['quote_default' => 0])) }}"
                    {{ request('quote_default') === '0' ? 'selected' : '' }}>
                    Not Shown
                </option>

            </select>

            {{-- Frequency tabs --}}
            <select onchange="window.location = this.value"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
               text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">

                <option value="{{ route('admin.products.index', request()->except(['frequency', 'page'])) }}"
                    {{ request('frequency', '') === '' ? 'selected' : '' }}>
                    All Frequencies
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['frequency', 'page']), ['frequency' => 'once_off'])) }}"
                    {{ request('frequency') === 'once_off' ? 'selected' : '' }}>
                    Once Off
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['frequency', 'page']), ['frequency' => 'monthly'])) }}"
                    {{ request('frequency') === 'monthly' ? 'selected' : '' }}>
                    Monthly
                </option>

                <option value="{{ route('admin.products.index', array_merge(request()->except(['frequency', 'page']), ['frequency' => 'annually'])) }}"
                    {{ request('frequency') === 'annually' ? 'selected' : '' }}>
                    Annually
                </option>

            </select>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Product</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Category</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Price Type</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Price</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Frequency</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($product->image_url)
                                    <x-image-preview
                                        src="{{ asset('storage/'.$product->image_url) }}"
                                        alt="{{ $product->name }}"
                                        thumbClass="w-16 h-16"
                                        thumbImageClass="w-16 h-16 rounded-lg object-cover flex-shrink-0 bg-gray-100"
                                        previewClass="w-[36rem] h-[36rem]"
                                    />
                                @else
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.products.show', $product) }}"
                                       class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                        {{ $product->name }}
                                    </a>
                                    @if($product->short_name)
                                        <div class="text-xs text-gray-400">{{ $product->short_name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $product->category?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $typeStyles = [
                                    'fixed'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                    'dropdown' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'hourly'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                ];
                                $typeStyle = $typeStyles[$product->price_type] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeStyle }}">
                                {{ ucfirst($product->price_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                            @if($product->price_type === 'fixed')
                                ${{ number_format($product->fixed_price, 2) }}
                            @elseif($product->price_type === 'dropdown')
                                ${{ number_format($product->price_min, 2) }} – ${{ number_format($product->price_max, 2) }}
                            @elseif($product->price_type === 'hourly')
                                ${{ number_format($product->hourly_rate, 2) }}/hr
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                            {{ $product->frequency_label }}
                        </td>
                        <td class="px-4 py-3">
                            @if($product->is_active)
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
                                <a href="{{ route('admin.products.show', $product) }}"
                                   class="text-gray-400 hover:text-blue-500 transition" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}"
                                   class="text-gray-400 hover:text-yellow-500 transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline"
                                      onsubmit="return confirm('Delete {{ $product->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                            </svg>
                            No products found.
                            <a href="{{ route('admin.products.create') }}" class="text-blue-500 hover:underline">Add your first product</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            @if($products->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            let debounceTimer;

            // 1. Automatic filter on typing (with debounce)
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);

                // Wait 500ms after the user stops typing to submit the form
                debounceTimer = setTimeout(() => {
                    searchForm.submit();
                }, 500);
            });

            // 2. Auto-clear when clicking the native 'X' button in type="search"
            searchInput.addEventListener('search', function () {
                if (this.value === '') {
                    // If input is empty, submit form immediately to clear search parameter
                    searchForm.submit();
                }
            });
        });
    </script>

</x-app-layout>

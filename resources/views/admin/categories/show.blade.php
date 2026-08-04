<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-3">

                {{-- Category swatch + name --}}
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden"
                     style="background-color: {{ $category->color ?? '#e5e7eb' }}">
                    @if($category->icon)
                        <img src="{{ asset('storage/'.$category->icon) }}"
                             alt="{{ $category->name }}"
                             class="w-full h-full object-cover">
                    @endif
                </div>

                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    {{ $category->name }}
                </h2>

                @if($category->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        Inactive
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.categories.edit', $category) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                      onsubmit="return confirm('Delete \'{{ $category->name }}\'? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-red-500 text-sm font-medium rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </form>

                <a href="{{ route('admin.categories.index') }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 w-full mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: product list --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Products
                            <span class="ml-1.5 text-xs font-normal text-gray-400">({{ $category->products->count() }})</span>
                        </h3>
                        <a href="{{ route('admin.products.create', ['category_id' => $category->id]) }}"
                           class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add product
                        </a>
                    </div>

                    @forelse($category->products as $product)
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-50 dark:border-gray-700/50 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            @if($product->image_url)
                                <img src="{{ asset('storage/'.$product->image_url) }}" alt="{{ $product->name }}"
                                     class="w-7 h-7 rounded-md object-cover flex-shrink-0 bg-gray-100">
                            @else
                                <div class="w-7 h-7 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                    </svg>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <a href="{{ route('admin.products.show', $product) }}"
                                   class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition truncate block">
                                    {{ $product->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $product->frequency_label }}</div>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    @if($product->price_type === 'fixed')
                                        ${{ number_format($product->fixed_price, 2) }}
                                    @elseif($product->price_type === 'dropdown')
                                        ${{ number_format($product->price_min, 2) }}+
                                    @elseif($product->price_type === 'hourly')
                                        ${{ number_format($product->hourly_rate, 2) }}/hr
                                    @endif
                                </div>
                                @if($product->is_active)
                                    <span class="text-xs text-green-600 dark:text-green-400">Active</span>
                                @else
                                    <span class="text-xs text-gray-400">Inactive</span>
                                @endif
                            </div>

                            <a href="{{ route('admin.products.edit', $product) }}"
                               class="text-gray-300 hover:text-yellow-500 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                            No products in this category yet.
                            <a href="{{ route('admin.products.create', ['category_id' => $category->id]) }}"
                               class="text-blue-500 hover:underline">Add one</a>.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Right: meta sidebar --}}
            <div class="space-y-6">

                {{-- Visual --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Appearance
                    </h3>

                    <div class="flex items-center gap-3 mb-4">

                        <div x-data="{show:false}"
                             class="relative">

                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl cursor-zoom-in overflow-hidden"
                                 style="background-color: {{ $category->color ?? '#e5e7eb' }}"
                                 @mouseenter="show=true"
                                 @mouseleave="show=false">

                                @if($category->icon)

                                    <img src="{{ asset('storage/'.$category->icon) }}"
                                         alt="{{ $category->name }}"
                                         class="w-full h-full object-contain">

                                @endif

                            </div>


                            {{-- Large Preview --}}
                            @if($category->icon)

                                <div x-show="show"
                                     x-transition
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm pointer-events-none">

                                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-3">

                                        <div class="w-[28rem] h-[28rem] rounded-xl flex items-center justify-center overflow-hidden"
                                             style="background-color: {{ $category->color ?? '#e5e7eb' }}">

                                            <img src="{{ asset('storage/'.$category->icon) }}"
                                                 alt="{{ $category->name }}"
                                                 class="w-full h-full object-contain">

                                        </div>

                                    </div>

                                </div>

                            @endif

                        </div>


                        <div>
                            <div class="text-xs text-gray-400 mb-0.5">
                                Colour
                            </div>

                            <div class="flex items-center gap-1.5">

                                <div class="w-4 h-4 rounded border border-gray-200 dark:border-gray-600"
                                     style="background-color: {{ $category->color ?? '#e5e7eb' }}">
                                </div>

                                <code class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $category->color ?? '—' }}
                                </code>

                            </div>
                        </div>

                    </div>
                </div>

                {{-- Details --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Details
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Slug</dt>
                            <dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $category->slug }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Sort order</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $category->sort_order }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Products</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $category->products->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $category->created_at->format('d M Y') }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Updated</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $category->updated_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>

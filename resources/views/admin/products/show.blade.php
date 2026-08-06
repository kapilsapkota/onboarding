<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    {{ $product->name }}
                </h2>
                @if($product->is_active)
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
                <a href="{{ route('admin.products.edit', $product) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                      onsubmit="return confirm('Delete {{ $product->name }}?')">
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

                <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Products
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: main detail --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Overview --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <div class="flex items-start gap-4">
                        @if($product->image_url)
                            <x-image-preview
                                src="{{ asset('storage/'.$product->image_url) }}"
                                alt="{{ $product->name }}"
                                thumbClass="w-16 h-16"
                                thumbImageClass="w-16 h-16 rounded-lg object-cover flex-shrink-0 bg-gray-100"
                                previewClass="w-[36rem] h-[36rem]"
                            />
{{--                            <img src="{{ asset('storage/'.$product->image_url) }}"--}}
{{--                                 alt="{{ $product->name }}"--}}
{{--                                 class="w-16 h-16 object-cover">--}}
                        @else
                            <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</h3>
                            @if($product->short_name)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product->short_name }}</p>
                            @endif
                            @if($product->category)
                                <span class="mt-1 inline-block text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded px-2 py-0.5">
                                    {{ $product->category->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($product->description)
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            {!! $product->description !!}
                        </p>
                    @endif
                </div>

                {{-- Scope Items --}}
                @if($product->scope_items && count($product->scope_items))
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                            Scope Items
                        </h3>
                        <ul class="space-y-2">
                            @foreach($product->scope_items as $item)
                                <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Notes --}}
                @if($product->notes)
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                            Internal Notes
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $product->notes }}</p>
                    </div>
                @endif

            </div>

            {{-- Right: pricing sidebar --}}
            <div class="space-y-6">
                @if($product->image_url)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Product Image
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between">
                            <dd>
                                @if($product->image_url)
                                    <img src="{{ asset('storage/'.$product->image_url) }}" alt="{{ $product->name }}"
                                         class="rounded-xl object-cover flex-shrink-0 bg-gray-100">
                                @else
                                    <div class="rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                        </svg>
                                    </div>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
                @endif

                {{-- Pricing --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Pricing
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Type</dt>
                            <dd>
                                @php
                                    $typeStyles = [
                                        'fixed'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                        'dropdown' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        'hourly'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeStyles[$product->price_type] ?? '' }}">
                                    {{ ucfirst($product->price_type) }}
                                </span>
                            </dd>
                        </div>

                        @if($product->price_type === 'fixed')
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Price</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">${{ number_format($product->fixed_price, 2) }}</dd>
                            </div>
                        @elseif($product->price_type === 'dropdown')
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Range</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    ${{ number_format($product->price_min, 2) }} – ${{ number_format($product->price_max, 2) }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Step</dt>
                                <dd class="text-sm text-gray-700 dark:text-gray-300">${{ number_format($product->price_increment, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400 mb-1.5">Options</dt>
                                <dd class="flex flex-wrap gap-1.5">
                                    @foreach($product->getPriceOptions() as $opt)
                                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-mono">
                                            ${{ number_format($opt, 2) }}
                                        </span>
                                    @endforeach
                                </dd>
                            </div>
                        @elseif($product->price_type === 'hourly')
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Rate</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">${{ number_format($product->hourly_rate, 2) }}/hr</dd>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Frequency</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $product->frequency_label }}</dd>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Setup Fee</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $product->setup_fee }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Meta --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Details
                    </h3>
                    <dl class="space-y-3">
                        @if($product->key_scope_keyword)
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Scope keyword</dt>
                                <dd class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $product->key_scope_keyword }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Sort order</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $product->sort_order }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $product->created_at->format('d M Y') }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Updated</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $product->updated_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>

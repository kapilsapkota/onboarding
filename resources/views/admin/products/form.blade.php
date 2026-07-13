<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                {{ isset($product) ? 'Edit Product' : 'New Product' }}
            </h2>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <p class="font-medium mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}">
            @csrf
            @if(isset($product)) @method('PUT') @endif

            <div class="space-y-6">

                {{-- Basic Info --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Basic Info
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Product Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}"
                                   required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. Website Design Package">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Short Name
                            </label>
                            <input type="text" name="short_name" value="{{ old('short_name', $product->short_name ?? '') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. Web Design">
                            <p class="mt-1 text-xs text-gray-400">Used in compact views and quote line items.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Category
                            </label>
                            <select name="category_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— No category —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea name="description" rows="3"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="What does this product include?">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Key Scope Keyword
                            </label>
                            <input type="text" name="key_scope_keyword"
                                   value="{{ old('key_scope_keyword', $product->key_scope_keyword ?? '') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. pages, hours">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Image URL
                            </label>
                            <input type="url" name="image_url"
                                   value="{{ old('image_url', $product->image_url ?? '') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="https://…">
                        </div>

                    </div>
                </div>

                {{-- Scope Items --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Scope Items
                    </h3>
                    <div id="scope-items" class="space-y-2">
                        @php $scopeItems = old('scope_items', $product->scope_items ?? []); @endphp
                        @forelse($scopeItems as $item)
                            <div class="flex gap-2 scope-item">
                                <input type="text" name="scope_items[]" value="{{ $item }}"
                                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. Up to 5 pages">
                                <button type="button" onclick="this.closest('.scope-item').remove()"
                                        class="p-2 text-gray-400 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <div class="flex gap-2 scope-item">
                                <input type="text" name="scope_items[]"
                                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g. Up to 5 pages">
                                <button type="button" onclick="this.closest('.scope-item').remove()"
                                        class="p-2 text-gray-400 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" id="add-scope-item"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add item
                    </button>
                </div>

                {{-- Pricing --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Pricing
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Price Type <span class="text-red-500">*</span>
                            </label>
                            <div class="flex flex-wrap gap-3" id="price-type-group">
                                @foreach(['fixed' => 'Fixed', 'dropdown' => 'Dropdown Range', 'hourly' => 'Hourly'] as $val => $label)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="price_type" value="{{ $val }}"
                                               {{ old('price_type', $product->price_type ?? 'fixed') === $val ? 'checked' : '' }}
                                               class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Fixed price --}}
                        <div id="field-fixed" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fixed Price ($)</label>
                                <input type="number" name="fixed_price" step="0.01" min="0"
                                       value="{{ old('fixed_price', $product->fixed_price ?? '') }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                        </div>

                        {{-- Dropdown range --}}
                        <div id="field-dropdown" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Price ($)</label>
                                <input type="number" name="price_min" step="0.01" min="0"
                                       value="{{ old('price_min', $product->price_min ?? '') }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Price ($)</label>
                                <input type="number" name="price_max" step="0.01" min="0"
                                       value="{{ old('price_max', $product->price_max ?? '') }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Increment ($)</label>
                                <input type="number" name="price_increment" step="0.01" min="0"
                                       value="{{ old('price_increment', $product->price_increment ?? '') }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                        </div>

                        {{-- Hourly --}}
                        <div id="field-hourly" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hourly Rate ($/hr)</label>
                                <input type="number" name="hourly_rate" step="0.01" min="0"
                                       value="{{ old('hourly_rate', $product->hourly_rate ?? '') }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequency</label>
                            <select name="frequency"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @foreach(['once_off' => 'Once Off', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ old('frequency', $product->frequency ?? 'once_off') === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                {{-- Notes & Settings --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Notes & Settings
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Notes</label>
                            <textarea name="notes" rows="3"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Internal notes, not shown to clients.">{{ old('notes', $product->notes ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input type="number" name="sort_order" min="0"
                                   value="{{ old('sort_order', $product->sort_order ?? 0) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-400">Lower numbers appear first.</p>
                        </div>

                        <div class="flex items-center gap-3 pt-6">
                            <button type="button" id="toggle-active" role="switch"
                                    aria-checked="{{ old('is_active', $product->is_active ?? true) ? 'true' : 'false' }}"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                                           {{ old('is_active', $product->is_active ?? true) ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                             {{ old('is_active', $product->is_active ?? true) ? 'translate-x-6' : 'translate-x-1' }}">
                                </span>
                            </button>
                            <input type="hidden" name="is_active" id="is-active-input"
                                   value="{{ old('is_active', $product->is_active ?? true) ? '1' : '0' }}">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Active (visible in quotes builder)</span>
                        </div>

                    </div>
                </div>

                {{-- Form actions --}}
                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.products.index') }}"
                       class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ isset($product) ? 'Save Changes' : 'Create Product' }}
                    </button>
                </div>

            </div>
        </form>
    </div>

        <script>
            // Price type toggle
            const priceFields = { fixed: 'field-fixed', dropdown: 'field-dropdown', hourly: 'field-hourly' };

            function updatePriceFields() {
                const selected = document.querySelector('input[name="price_type"]:checked')?.value;
                Object.entries(priceFields).forEach(([type, id]) => {
                    const el = document.getElementById(id);
                    if (el) el.style.display = (type === selected) ? '' : 'none';
                });
            }

            document.querySelectorAll('input[name="price_type"]').forEach(r => {
                r.addEventListener('change', updatePriceFields);
            });

            updatePriceFields();

            // Scope items
            document.getElementById('add-scope-item').addEventListener('click', () => {
                const container = document.getElementById('scope-items');
                const div = document.createElement('div');
                div.className = 'flex gap-2 scope-item';
                div.innerHTML = `
                <input type="text" name="scope_items[]"
                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                       placeholder="e.g. Up to 5 pages">
                <button type="button" onclick="this.closest('.scope-item').remove()"
                        class="p-2 text-gray-400 hover:text-red-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
                container.appendChild(div);
            });

            // Active toggle
            const toggleBtn = document.getElementById('toggle-active');
            const activeInput = document.getElementById('is-active-input');

            toggleBtn.addEventListener('click', () => {
                const isActive = toggleBtn.getAttribute('aria-checked') === 'true';
                toggleBtn.setAttribute('aria-checked', !isActive);
                activeInput.value = isActive ? '0' : '1';
                toggleBtn.classList.toggle('bg-blue-600', !isActive);
                toggleBtn.classList.toggle('bg-gray-200', isActive);
                toggleBtn.classList.toggle('dark:bg-gray-600', isActive);
                const knob = toggleBtn.querySelector('span');
                knob.classList.toggle('translate-x-6', !isActive);
                knob.classList.toggle('translate-x-1', isActive);
            });
        </script>

</x-app-layout>

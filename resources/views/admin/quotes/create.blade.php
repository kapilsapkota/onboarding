<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                {{ isset($quote) ? 'Edit Quote — ' . $quote->quote_number : 'New Quote' }}
            </h2>
            <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Quotes
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8"
         x-data="quoteBuilder(
             {{ $categories->toJson() }},
             {{ isset($quote) ? $quote->items->toJson() : '[]' }}
         )">
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              enctype="multipart/form-data"
              action="{{ isset($quote) ? route('admin.quotes.update', $quote) : route('admin.quotes.store') }}"
              @submit.prevent="submitForm($event)">
            @csrf
            @if(isset($quote)) @method('PUT') @endif

            {{-- Hidden payload --}}
            <input type="hidden" name="items" x-ref="itemsField">

            {{-- ================================================================
                 SECTION 1 — Client Information
            ================================================================ --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-5">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Client Information
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    {{-- Client Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Client Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="client_name"
                               value="{{ old('client_name', $quote->client_name ?? '') }}"
                               required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Contact Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Name</label>
                        <input type="text" name="contact_name"
                               value="{{ old('contact_name', $quote->contact_name ?? '') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email
                            <span class="text-xs text-gray-400 font-normal">(quote sent from system)</span>
                        </label>
                        <input type="email" name="email"
                               value="{{ old('email', $quote->email ?? '') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Mobile --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Mobile
                            <span class="text-xs text-gray-400 font-normal">(SMS quote link)</span>
                        </label>
                        <input type="text" name="mobile"
                               value="{{ old('mobile', $quote->mobile ?? '') }}"
                               placeholder="+61 4XX XXX XXX"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Website --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="text" name="website"
                               value="{{ old('website', $quote->website ?? '') }}"
                               placeholder="https://example.com.au"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Logo
                        </label>

                        <input
                            type="file"
                            name="logo"
                            accept="image/*"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">

                        @if(!empty($quote->logo_path))
                            <img src="{{ Storage::url($quote->logo_path) }}"
                                 class="mt-2 h-16 rounded"
                                 alt="Logo">
                        @endif
                    </div>
                </div>
            </div>

            {{-- ================================================================
                 SECTION 2 — Line Items
            ================================================================ --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-5">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Quote Items
                    </h3>
                    <button type="button"
                            @click="showCatalog = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Item
                    </button>
                </div>

                {{-- Items table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-left w-40">Category</th>
                            <th class="px-4 py-3 text-left">Product / Scope</th>
                            <th class="px-4 py-3 text-left w-44">Price (ex-GST)</th>
                            <th class="px-4 py-3 text-left w-28">Hours</th>
                            <th class="px-4 py-3 text-left w-28">Frequency</th>
                            <th class="px-4 py-3 text-right w-32">Total inc. GST</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="group">
                                {{-- Category --}}
                                <td class="px-4 py-3 align-top">
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="item.category_name"></span>
                                </td>

                                {{-- Product name + scope --}}
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 mb-1" x-text="item.product_name"></div>
                                    <textarea
                                        x-model="item.scope_of_works"
                                        rows="4"
                                        class="w-full text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600 rounded-md p-1.5 bg-gray-50 dark:bg-gray-900 resize-y focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Scope of works…"></textarea>
                                </td>

                                {{-- Price --}}
                                <td class="px-4 py-3 align-top">
                                    <template x-if="item.price_type === 'dropdown'">
                                        <div>
                                            <select x-model.number="item.unit_price"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                                                <template x-for="opt in getPriceOptions(item)" :key="opt">
                                                    <option :value="opt" x-text="'$' + opt.toLocaleString('en-AU', {minimumFractionDigits:0})"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </template>
                                    <template x-if="item.price_type === 'fixed'">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100"
                                             x-text="'$' + Number(item.unit_price).toLocaleString('en-AU', {minimumFractionDigits:2})">
                                        </div>
                                    </template>
                                    <div class="text-xs text-gray-400 mt-1">
                                        + <span x-text="'$' + (Number(item.unit_price) * 0.1).toLocaleString('en-AU', {minimumFractionDigits:2})"></span> GST
                                    </div>
                                </td>

                                {{-- Hours --}}
                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    <template x-if="item.hourly_rate > 0">
                                        <div>
                                            <span class="font-medium" x-text="calculateHours(item)"></span>
                                            <span class="text-xs text-gray-400"> hrs</span>
                                            <div class="text-xs text-gray-400" x-text="'@ $' + item.hourly_rate + '/hr'"></div>
                                        </div>
                                    </template>
                                    <template x-if="!item.hourly_rate">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>

                                {{-- Frequency --}}
                                <td class="px-4 py-3 align-top">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                                              x-text="frequencyLabel(item.frequency)">
                                        </span>
                                </td>

                                {{-- Total inc. GST --}}
                                <td class="px-4 py-3 align-top text-right font-semibold text-gray-900 dark:text-gray-100">
                                    <span x-text="'$' + (Number(item.unit_price) * 1.1).toLocaleString('en-AU', {minimumFractionDigits:2})"></span>
                                </td>

                                {{-- Remove --}}
                                <td class="px-4 py-3 align-top">
                                    <button type="button"
                                            @click="removeItem(index)"
                                            class="text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        {{-- Empty state --}}
                        <tr x-show="items.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                                No items added yet.
                                <button type="button" @click="showCatalog = true" class="text-blue-500 hover:underline">Add a product or service</button>.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <div class="w-72 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal (ex. GST)</span>
                            <span x-text="'$' + subtotal.toLocaleString('en-AU', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>GST (10%)</span>
                            <span x-text="'$' + gst.toLocaleString('en-AU', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                        </div>
                        <div class="flex justify-between font-bold text-base text-gray-900 dark:text-gray-100 pt-2 border-t border-gray-200 dark:border-gray-600">
                            <span>Total (inc. GST)</span>
                            <span x-text="'$' + total.toLocaleString('en-AU', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================
                 SECTION 3 — Notes & Actions
            ================================================================ --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-5 p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Internal notes (not shown on the quote)…">{{ old('notes', $quote->notes ?? '') }}</textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3 justify-end">
                <a href="{{ route('admin.quotes.index') }}"
                   class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm">
                    {{ isset($quote) ? 'Update Quote' : 'Save Quote' }}
                </button>
            </div>

        </form>
            <div x-show="showCatalog"
             x-transition.opacity
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display:none">

            <div class="fixed inset-0 bg-black/40" @click="showCatalog = false"></div>
                <div class="relative min-h-screen flex items-start justify-center pt-10 px-4 pb-10">
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl"
                     @click.stop>
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Product Catalogue</h3>
                        <button type="button" @click="showCatalog = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Category tabs --}}
                    <div class="px-6 pt-4 flex gap-2 overflow-x-auto pb-0 border-b border-gray-100 dark:border-gray-700">
                        <template x-for="(cat, ci) in categories" :key="cat.id">
                            <button type="button"
                                    @click="activeCategory = ci"
                                    :class="activeCategory === ci
                                        ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 transition">
                                <span x-text="cat.name"></span>
                                <span class="ml-1 text-xs opacity-60" x-text="'(' + cat.active_products.length + ')'"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Products grid --}}
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto">
                        <template x-for="product in (categories[activeCategory]?.active_products ?? [])" :key="product.id">
                            <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-4 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-sm transition cursor-pointer"
                                 @click="addItem(product); showCatalog = false">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm" x-text="product.short_name || product.name"></div>
                                        <div class="text-xs text-gray-400 mt-0.5" x-text="product.key_scope_keyword"></div>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                                          x-text="frequencyLabel(product.frequency)">
                                    </span>
                                </div>

                                {{-- Price display --}}
                                <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 mb-2">
                                    <template x-if="product.price_type === 'fixed'">
                                        <span x-text="'$' + Number(product.fixed_price).toLocaleString('en-AU') + ' ex-GST'"></span>
                                    </template>
                                    <template x-if="product.price_type === 'dropdown'">
                                        <span x-text="'$' + Number(product.price_min).toLocaleString('en-AU') + ' – $' + Number(product.price_max).toLocaleString('en-AU') + ' ex-GST'"></span>
                                    </template>
                                </div>

                                {{-- Scope preview (first 4 items) --}}
                                <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                                    <template x-for="(scope, si) in (product.scope_items || []).slice(0, 4)" :key="si">
                                        <li class="flex items-start gap-1">
                                            <svg class="w-3 h-3 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-text="scope"></span>
                                        </li>
                                    </template>
                                    <template x-if="(product.scope_items || []).length > 4">
                                        <li class="text-gray-400" x-text="'+ ' + ((product.scope_items || []).length - 4) + ' more…'"></li>
                                    </template>
                                </ul>

                                <div class="mt-3 text-xs text-blue-600 dark:text-blue-400 font-medium">Click to add →</div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <script>
            function quoteBuilder(categories, existingItems) {
                return {
                    categories: categories,
                    activeCategory: 0,
                    showCatalog: false,
                    items: [],

                    init() {
                        // Hydrate existing items when editing
                        if (existingItems && existingItems.length) {
                            existingItems.forEach(item => {
                                // Determine price_type from whether product has dropdown
                                this.items.push({
                                    product_id:        item.product_id,
                                    category_name:     item.category_name,
                                    product_name:      item.product_name,
                                    scope_of_works:    item.scope_of_works ?? '',
                                    key_scope_keyword: item.key_scope_keyword ?? '',
                                    price_type:        item.product ? item.product.price_type : 'fixed',
                                    price_min:         item.product ? parseFloat(item.product.price_min) : 0,
                                    price_max:         item.product ? parseFloat(item.product.price_max) : 0,
                                    price_increment:   item.product ? parseFloat(item.product.price_increment) : 500,
                                    unit_price:        parseFloat(item.unit_price),
                                    hourly_rate:       item.hourly_rate ? parseFloat(item.hourly_rate) : null,
                                    frequency:         item.frequency,
                                    image_url:         item.image_url ?? '',
                                    notes:             item.notes ?? '',
                                });
                            });
                        }
                    },

                    addItem(product) {
                        const defaultPrice = product.price_type === 'fixed'
                            ? parseFloat(product.fixed_price)
                            : parseFloat(product.price_min);

                        this.items.push({
                            product_id:        product.id,
                            category_name:     product.category?.name ?? '',
                            product_name:      product.name,
                            scope_of_works:    (product.scope_items || []).join('\n'),
                            key_scope_keyword: product.key_scope_keyword ?? '',
                            price_type:        product.price_type,
                            price_min:         parseFloat(product.price_min) || 0,
                            price_max:         parseFloat(product.price_max) || 0,
                            price_increment:   parseFloat(product.price_increment) || 500,
                            unit_price:        defaultPrice,
                            hourly_rate:       product.hourly_rate ? parseFloat(product.hourly_rate) : null,
                            frequency:         product.frequency,
                            image_url:         product.image_url ?? '',
                            notes:             product.notes ?? '',
                        });
                    },

                    removeItem(index) {
                        this.items.splice(index, 1);
                    },

                    getPriceOptions(item) {
                        if (item.price_type !== 'dropdown') return [];
                        const options = [];
                        const step = item.price_increment || 500;
                        for (let p = item.price_min; p <= item.price_max + 0.001; p += step) {
                            options.push(Math.round(p * 100) / 100);
                        }
                        return options;
                    },

                    calculateHours(item) {
                        if (!item.hourly_rate || item.hourly_rate <= 0) return null;
                        return Math.round(item.unit_price / item.hourly_rate);
                    },

                    frequencyLabel(freq) {
                        const map = {
                            monthly:   'Monthly',
                            quarterly: 'Quarterly',
                            yearly:    'Yearly',
                            once_off:  'Once Off',
                        };
                        return map[freq] ?? 'Once Off';
                    },

                    get subtotal() {
                        return this.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0), 0);
                    },

                    get gst() {
                        return Math.round(this.subtotal * 0.10 * 100) / 100;
                    },

                    get total() {
                        return Math.round((this.subtotal + this.gst) * 100) / 100;
                    },

                    submitForm(e) {
                        this.$refs.itemsField.value = JSON.stringify(this.items);
                        e.target.submit();
                    },
                };
            }
        </script>

</x-app-layout>

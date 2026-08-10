<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                {{ isset($quote) ? 'Edit Quote - '.$quote->quote_number : 'New Quote' }}
            </h2>

            <a href="{{ route('admin.quotes.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Quotes
            </a>
        </div>
    </x-slot>

    <div
        class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8"
        x-data="quoteBuilder(
            {{ $categories->toJson() }},
            {{ isset($quote) ? $quote->items->load('product')->toJson() : '[]' }}
        )"
    >

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ isset($quote)
                ? route('admin.quotes.update', $quote)
                : route('admin.quotes.store') }}"
            @submit.prevent="submitForm($event)"
        >

            @csrf

            @isset($quote)
                @method('PUT')
            @endisset

            {{-- FIX 2: Hidden field that receives the serialised items JSON on submit --}}
            <input type="hidden" name="items" x-ref="itemsField">

            {{-- ------------------------------------------------------------------ --}}
            {{-- CLIENT INFORMATION                                                  --}}
            {{-- ------------------------------------------------------------------ --}}

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow mb-6">

                <div class="border-b px-6 py-4">
                    <h3 class="font-semibold">Client Information</h3>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Company Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            required
                            name="client_name"
                            type="text"
                            value="{{ old('client_name', $quote->client_name ?? '') }}"
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Contact Person <span class="text-red-500">*</span>
                        </label>
                        <input
                            name="contact_name"
                            type="text"
                            value="{{ old('contact_name', $quote->contact_name ?? '') }}"
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            name="email"
                            type="email"
                            value="{{ old('email', $quote->email ?? '') }}"
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile</label>
                        <input
                            name="mobile"
                            type="text"
                            value="{{ old('mobile', $quote->mobile ?? '') }}"
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Website</label>
                        <input
                            name="website"
                            type="text"
                            value="{{ old('website', $quote->website ?? '') }}"
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Expires at</label>

                        <input
                            id="expires_at"
                            name="expires_at"
                            type="date"
                            value="{{ old(
                                'expires_at',
                                isset($quote)
                                    ? optional($quote->expires_at)->toDateString()
                                    : now()->addMonth()->toDateString()
                            ) }}"
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-5">
                    <x-image-upload
                        name="logo"
                        remove-name="remove_logo"
                        label="Client Logo"
                        :current="$quote->logo_url ?? null"
                    />
                </div>

            </div>

            {{-- ------------------------------------------------------------------ --}}
            {{-- QUOTE ITEMS                                                         --}}
            {{-- ------------------------------------------------------------------ --}}

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow">

                <div class="border-b px-6 py-4 flex items-center justify-between">
                    <h3 class="font-semibold">Quote Items</h3>
                    <button
                        type="button"
                        @click="addBlankRow()"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg"
                    >
                        + Add Row
                    </button>
                </div>

                <div class="overflow-visible">

                    <table class="min-w-full">

                        <thead class="bg-gray-50 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left w-96">Product</th>
                            <th class="px-4 py-3 text-center w-24">Qty</th>
                            <th class="px-4 py-3 text-left w-44">Unit Price</th>
                            <th class="px-4 py-3 text-left w-44">Setup Fee</th>
                            <th class="px-4 py-3 text-center w-24">Hours</th>
                            <th class="px-4 py-3 text-center w-32">Frequency</th>
                            <th class="px-4 py-3 text-right w-36">Total</th>
                            <th class="w-12"></th>
                        </tr>
                        </thead>

                        <template x-for="(item, index) in items" :key="index">
                            <tbody class="divide-y">

                            {{-- MAIN ITEM ROW --}}
                            <tr class="align-top">

                                {{-- PRODUCT --}}
                                <td class="p-4 relative">

                                    <input
                                        type="text"
                                        x-model="item.search"
                                        @focus="openSearch(item)"
                                        @input="filterProducts(item)"
                                        @keydown.escape="item.showResults = false"
                                        placeholder="Search product..."
                                        class="w-full rounded-lg border-gray-300"
                                    >

                                    {{-- Autocomplete dropdown --}}
                                    <div
                                        x-show="item.showResults"
                                        x-cloak
                                        @click.outside="item.showResults = false"
                                        class="absolute left-4 right-4 mt-1 bg-white border rounded-lg shadow-lg max-h-64 overflow-y-auto z-50"
                                    >
                                        <template
                                            x-for="product in item.filteredProducts"
                                            :key="product.id"
                                        >
                                            <button
                                                type="button"
                                                @click="selectProduct(item, product)"
                                                class="block w-full text-left px-4 py-3 hover:bg-blue-50 border-b"
                                            >
                                                <div class="font-medium" x-text="product.name"></div>

                                                <div class="text-xs text-gray-500">
                                                    <span x-text="product.category?.name"></span>
                                                    •

                                                    <span
                                                        x-show="product.price_type == 'fixed'"
                                                        x-text="'$' + Number(product.fixed_price).toLocaleString()"
                                                    ></span>

                                                    <span
                                                        x-show="product.price_type == 'dropdown'"
                                                        x-text="'$' + Number(product.price_min).toLocaleString() + ' – $' + Number(product.price_max).toLocaleString()"
                                                    ></span>
                                                </div>
                                            </button>
                                        </template>

                                        <div
                                            x-show="item.filteredProducts.length === 0"
                                            class="px-4 py-3 text-sm text-gray-400"
                                        >
                                            No products found.
                                        </div>
                                    </div>

                                    {{-- Selected product --}}
                                    <template x-if="item.product_id">
                                        <div class="mt-2">
                                            <div
                                                class="font-medium text-sm"
                                                x-text="item.product_name"
                                            ></div>

                                            <div
                                                class="text-xs text-gray-500"
                                                x-text="item.category_name"
                                            ></div>

                                            <button
                                                type="button"
                                                @click="item.showDetails = !item.showDetails"
                                                class="mt-2 text-xs font-medium text-blue-600 hover:text-blue-800"
                                            >
            <span
                x-text="item.showDetails ? '− Hide details' : '+ Edit details'"
            ></span>
                                            </button>
                                        </div>
                                    </template>

                                </td>

                                {{-- QTY --}}
                                <td class="p-4">
                                    <input
                                        type="number"
                                        min="1"
                                        x-model.number="item.quantity"
                                        class="w-20 rounded-lg border-gray-300 text-center"
                                    >
                                </td>

                                {{-- UNIT PRICE --}}
                                <td class="p-4">
                                    <div class="flex items-center gap-2 w-36">
                                        <span class="text-sm text-gray-500">$</span>

                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            x-model.number="item.unit_price"
                                            class="w-full rounded-lg border-gray-300 text-right"
                                        >
                                    </div>

                                    <template x-if="item.price_type === 'dropdown'">
                                        <div class="mt-1 text-xs text-gray-400">
                                            Suggested:
                                            $<span x-text="Number(item.price_min).toLocaleString()"></span>
                                            –
                                            $<span x-text="Number(item.price_max).toLocaleString()"></span>
                                        </div>
                                    </template>
                                </td>

                                {{-- SETUP FEE --}}
                                <td class="p-4">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        x-model.number="item.setup_fee"
                                        class="w-36 rounded-lg border-gray-300 text-right"
                                    >
                                </td>

                                {{-- HOURS --}}
                                <td class="p-4 text-center">

                                    <template x-if="item.hourly_rate">
                                        <div>
                        <span
                            class="font-medium"
                            x-text="calculateHours(item)"
                        ></span>

                                            <span class="text-xs text-gray-400">
                            hrs
                        </span>
                                        </div>
                                    </template>

                                    <template x-if="!item.hourly_rate">
                                        <span class="text-gray-400">—</span>
                                    </template>

                                </td>

                                {{-- FREQUENCY --}}
                                <td class="p-4 text-center">
                <span
                    class="inline-flex px-2 py-1 rounded bg-indigo-50 text-indigo-700 text-xs"
                    x-text="frequencyLabel(item.frequency)"
                ></span>
                                </td>

                                {{-- LINE TOTAL --}}
                                <td class="p-4 text-right font-semibold">
                <span
                    x-text="'$' + lineTotal(item).toLocaleString('en-AU', {
                        minimumFractionDigits: 2
                    })"
                ></span>
                                </td>

                                {{-- REMOVE --}}
                                <td class="p-4">
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        class="text-gray-400 hover:text-red-600"
                                    >
                                        ✕
                                    </button>
                                </td>

                            </tr>

                            {{-- ITEM DETAILS ROW --}}
                            <template x-if="item.product_id && item.showDetails">
                                <tr class="bg-gray-50 dark:bg-gray-900/30">
                                    <td colspan="8" class="px-4 py-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                                    Scope of Works
                                                </label>

                                                <textarea
                                                    rows="3"
                                                    x-model="item.scope_of_works"
                                                    class="w-full rounded-lg border-gray-300 text-sm"
                                                    placeholder="Scope of works..."
                                                ></textarea>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                                    Key Scope Keyword
                                                </label>

                                                <textarea
                                                    rows="3"
                                                    x-model="item.key_scope_keyword"
                                                    class="w-full rounded-lg border-gray-300 text-sm"
                                                    placeholder="Scope of works..."
                                                ></textarea>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                                    Notes
                                                </label>

                                                <textarea
                                                    rows="3"
                                                    x-model="item.notes"
                                                    class="w-full rounded-lg border-gray-300 text-sm"
                                                    placeholder="Additional notes..."
                                                ></textarea>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            </template>

                            </tbody>
                        </template>
                        <tfoot>
                        <tr>
                            <td colspan="8" class="p-4 border-t">
                                <button
                                    type="button"
                                    @click="addBlankRow()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg"
                                >
                                    + Add Product
                                </button>
                            </td>
                        </tr>
                        </tfoot>

                    </table>

                </div>

                {{-- TOTALS --}}
                <div class="border-t px-6 py-5 flex justify-end">
                    <div class="w-80 space-y-3 text-sm">

                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal (ex GST)</span>
                            <span x-text="'$' + subtotal.toLocaleString('en-AU', { minimumFractionDigits: 2 })"></span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">GST (10%)</span>
                            <span x-text="'$' + gst.toLocaleString('en-AU', { minimumFractionDigits: 2 })"></span>
                        </div>

                        <div class="border-t pt-3 flex justify-between font-bold text-lg">
                            <span>Total Inc GST</span>
                            <span x-text="'$' + total.toLocaleString('en-AU', { minimumFractionDigits: 2 })"></span>
                        </div>

                    </div>
                </div>

            </div>

            {{-- NOTES --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow mt-6 p-6">
                <label class="block text-sm font-medium mb-2">Internal Notes</label>
                <textarea
                    name="notes"
                    rows="4"
                    class="w-full rounded-lg border-gray-300"
                    placeholder="Internal notes..."
                >{{ old('notes', $quote->notes ?? '') }}</textarea>
            </div>

            {{-- ACTIONS --}}
            <div class="flex justify-end gap-3 mt-6">

                <a
                    href="{{ route('admin.quotes.index') }}"
                    class="px-5 py-2.5 rounded-lg bg-gray-100"
                >Cancel</a>

                <button
                    type="submit"
                    class="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-semibold"
                >
                    {{ isset($quote) ? 'Update Quote' : 'Save Quote' }}
                </button>

            </div>

        </form>

    </div>

    <script>

        function quoteBuilder(categories, existingItems)
        {
            return {

                categories,
                products: [],
                items: [],

                // ---------------------------------------------------------------
                // init() — called automatically by Alpine v3. Do NOT add
                // x-init="init()" on the element or items will be pushed twice.
                // ---------------------------------------------------------------

                init()
                {
                    this.products = categories.flatMap(category =>
                        (category.active_products || []).map(product => {
                            product.category_name = category.name;
                            return product;
                        })
                    );

                    if (existingItems && existingItems.length) {
                        existingItems.forEach(item => {
                            let product = item.product;
                            this.items.push({
                                product_id:        item.product_id,
                                product_name:      item.product_name  || product?.name || '',
                                category_name:     product?.category?.name || item.category_name || '',
                                search:            item.product_name  || product?.name || '',
                                filteredProducts:  [],
                                showResults:       false,
                                quantity:          Number(item.quantity   ?? 1),
                                price_type:        product?.price_type    || 'fixed',
                                price_min:         Number(product?.price_min      ?? 0),
                                price_max:         Number(product?.price_max      ?? 0),
                                price_increment:   Number(product?.price_increment ?? 500),
                                unit_price:        Number(item.unit_price  ?? 0),
                                hourly_rate:       item.hourly_rate ? Number(item.hourly_rate) : null,
                                frequency:         item.frequency  || product?.frequency || 'once_off',
                                setup_fee:    Number(item.setup_fee ?? 0),
                                key_scope_keyword: item.key_scope_keyword || '',
                                scope_of_works:    item.scope_of_works || '',
                                notes:             item.notes || '',
                            });
                        });
                    } else {
                        this.products
                            .filter(product => product.quote_default)
                            .forEach(product => {
                            this.items.push({
                                product_id:       product.id,
                                product_name:     product.name,
                                category_name:    product.category_name,
                                search:           product.name,
                                filteredProducts: [],
                                showResults:      false,
                                quantity:         1,
                                price_type:       product.price_type      || 'fixed',
                                price_min:        Number(product.price_min       ?? 0),
                                price_max:        Number(product.price_max       ?? 0),
                                price_increment:  Number(product.price_increment ?? 500),
                                unit_price:       product.price_type === 'fixed'
                                    ? Number(product.fixed_price ?? 0)
                                    : Number(product.price_min  ?? 0),
                                hourly_rate:      product.hourly_rate ? Number(product.hourly_rate) : null,
                                frequency:        product.frequency || 'once_off',
                                setup_fee:    Number(product.setup_fee ?? 0),
                                key_scope_keyword: product.key_scope_keyword || '',
                                scope_of_works:   (product.scope_items || [])
                                    .map(s => s.replace(/^[-•]\s*/, ''))
                                    .join("\n"),
                                notes:             product.notes || '',
                            });
                        });
                    }
                },

                // ---------------------------------------------------------------
                // Add blank row
                // ---------------------------------------------------------------

                addBlankRow()
                {
                    this.items.push({
                        product_id:        null,
                        product_name:      '',
                        category_name:     '',
                        search:            '',
                        filteredProducts:  [],
                        showResults:       true,
                        showDetails:       true,
                        quantity:          1,
                        price_type:         'fixed',
                        price_min:         0,
                        price_max:         0,
                        price_increment:   500,
                        unit_price:        0,
                        hourly_rate:       null,
                        frequency:         'once_off',
                        setup_fee:         0,
                        key_scope_keyword: '',
                        scope_of_works:    '',
                        notes:             '',
                    });
                },
                isProductSelected(productId, currentItem = null)
                {
                    return this.items.some(item =>
                        item !== currentItem &&
                        Number(item.product_id) === Number(productId)
                    );
                },

                // ---------------------------------------------------------------
                // Open search — populate the list before the dropdown appears
                // ---------------------------------------------------------------

                openSearch(item)
                {
                    if (!item.filteredProducts.length) {
                        item.filteredProducts = this.products
                            .filter(product => !this.isProductSelected(product.id, item))
                            .slice(0, 20);
                    }

                    item.showResults = true;
                },

                // ---------------------------------------------------------------
                // Filter products as the user types
                // ---------------------------------------------------------------

                filterProducts(item)
                {
                    let search = item.search.toLowerCase().trim();

                    item.filteredProducts = this.products
                        .filter(p => !this.isProductSelected(p.id, item))
                        .filter(p =>
                            !search
                            || p.name.toLowerCase().includes(search)
                            || p.short_name?.toLowerCase().includes(search)
                            || p.category_name?.toLowerCase().includes(search)
                        )
                        .slice(0, 20);

                    item.showResults = true;
                },

                // ---------------------------------------------------------------
                // Select a product from the dropdown
                // ---------------------------------------------------------------

                selectProduct(item, product)
                {
                    if (this.isProductSelected(product.id, item)) {
                        item.search = '';
                        item.showResults = false;

                        alert(`"${product.name}" is already added to this quote.`);

                        return;
                    }

                    item.product_id      = product.id;
                    item.product_name    = product.name;
                    item.category_name   = product.category_name;
                    item.search          = product.name;
                    item.showResults     = false;

                    item.price_type      = product.price_type;
                    item.price_min       = Number(product.price_min || 0);
                    item.price_max       = Number(product.price_max || 0);
                    item.price_increment = Number(product.price_increment || 500);

                    item.unit_price = product.price_type === 'fixed'
                        ? Number(product.fixed_price)
                        : Number(product.price_min);

                    item.hourly_rate = product.hourly_rate
                        ? Number(product.hourly_rate)
                        : null;

                    item.frequency = product.frequency || 'once_off';
                    item.setup_fee = Number(product.setup_fee ?? 0);

                    item.key_scope_keyword = product.key_scope_keyword || '';

                    item.scope_of_works = (product.scope_items || [])
                        .map(scope => scope.replace(/^[-•]\s*/, ''))
                        .join("\n");

                    item.notes = product.notes || '';

                    // Open details when a new product is selected
                    item.showDetails = true;
                },

                // ---------------------------------------------------------------
                // Remove a row
                // ---------------------------------------------------------------

                removeItem(index)
                {
                    this.items.splice(index, 1);
                },

                // ---------------------------------------------------------------
                // Price dropdown step options
                // ---------------------------------------------------------------

                getPriceOptions(item)
                {
                    let options = [];
                    let step    = item.price_increment || 500;

                    for (let price = item.price_min; price <= item.price_max; price += step) {
                        options.push(Math.round(price * 100) / 100);
                    }

                    return options;
                },

                // ---------------------------------------------------------------
                // Calculations
                // ---------------------------------------------------------------

                lineTotal(item)
                {
                    return Number(item.quantity || 0) * Number(item.unit_price || 0)
                        + Number(item.setup_fee || 0);
                },

                calculateHours(item)
                {
                    if (!item.hourly_rate) return 0;

                    return Math.round(
                        (Number(item.quantity) * Number(item.unit_price))
                        / Number(item.hourly_rate)
                    );
                },

                frequencyLabel(value)
                {
                    const labels = {
                        monthly:   'Monthly',
                        quarterly: 'Quarterly',
                        yearly:    'Yearly',
                        once_off:  'Once Off',
                    };

                    return labels[value] ?? 'Once Off';
                },

                get subtotal()
                {
                    return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
                },

                get gst()
                {
                    return Math.round(this.subtotal * 0.10 * 100) / 100;
                },

                get total()
                {
                    return Math.round((this.subtotal + this.gst) * 100) / 100;
                },

                // ---------------------------------------------------------------
                // Submit — serialise items into the hidden field then post
                // ---------------------------------------------------------------

                submitForm(event)
                {
                    this.$refs.itemsField.value = JSON.stringify(
                        this.items.map(item => ({
                            product_id:     item.product_id,
                            product_name:   item.product_name,
                            category_name:  item.category_name,
                            quantity:       item.quantity,
                            unit_price:     item.unit_price,
                            hourly_rate:    item.hourly_rate,
                            frequency:      item.frequency,
                            setup_fee:      item.setup_fee,
                            scope_of_works: item.scope_of_works,
                            key_scope_keyword: item.key_scope_keyword,
                            notes: item.notes,
                        }))
                    );

                    event.target.submit();
                },

            };
        }

    </script>

</x-app-layout>

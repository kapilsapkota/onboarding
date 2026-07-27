@php use Illuminate\Support\Facades\Storage; use Illuminate\Support\Str; @endphp
<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.index') }}"
               class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                {{ isset($product) ? 'Edit Product : '. $product->name : 'New Product' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6 w-full mx-auto sm:px-6 lg:px-8">

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
              enctype="multipart/form-data"
              action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}">
            @csrf
            @if(isset($product)) @method('PUT') @endif

            <div class="space-y-6">

                {{-- Basic Info --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Basic Info
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

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

                        <div class="sm:col-span-2 rich-text-wrapper">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>

                            <!-- The interactive editor container -->
                            <div class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm">
                                <div class="quill-editor min-h-[120px] dark:text-gray-200">
                                    {!! old('description', $product->description ?? '') !!}
                                </div>
                            </div>

                            <!-- Hidden textarea to pass data back to Laravel -->
                            <textarea name="description" class="hidden-quill-input hidden"></textarea>
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

                        {{-- Logo upload --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Product Image
                            </label>

                            @php $currentLogo = old('image_url', $product->image_url ?? null); @endphp

                            {{-- Current logo preview --}}
                            @if($currentLogo)
                                <div id="current-logo" class="mb-3 flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <img src="{{ Str::startsWith($currentLogo, 'http') ? $currentLogo : Storage::url($currentLogo) }}"
                                         alt="Current Image"
                                         class="h-12 w-12 object-contain rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 p-1">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 font-medium truncate">Current logo</p>
                                        <p class="text-xs text-gray-400">Upload a new file to replace it</p>
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs text-red-500 hover:text-red-600 cursor-pointer">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded text-red-500">
                                        Remove
                                    </label>
                                </div>
                            @endif

                            {{-- Drop zone --}}
                            <div id="logo-dropzone"
                                 class="relative flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 text-center cursor-pointer
                                        hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition group">

                                {{-- Preview (hidden until file chosen) --}}
                                <img id="logo-preview" src="#" alt="Preview"
                                     class="hidden h-16 w-16 object-contain rounded bg-white border border-gray-200 dark:border-gray-700 p-1 mx-auto">

                                <div id="logo-placeholder">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-1 group-hover:text-blue-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">Click to upload</span>
                                        or drag &amp; drop
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">PNG, JPG, WebP, SVG, GIF · up to 5 MB · auto-compressed to ≤50 KB</p>
                                </div>

                                <p id="logo-filename" class="hidden text-sm text-gray-600 dark:text-gray-300 font-medium"></p>

                                <input type="file" id="logo-input" name="logo"
                                       accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </div>

                            @error('logo')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <p class="mt-1.5 text-xs text-gray-400">
                                Large images are automatically resized and compressed server-side. The stored images will always be ≤ 50 KB for use in PDF generation.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Scope Items --}}
{{--                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">--}}
{{--                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">--}}
{{--                        Scope Items--}}
{{--                    </h3>--}}
{{--                    <div id="scope-items" class="space-y-2">--}}
{{--                        @php $scopeItems = old('scope_items', $product->scope_items ?? []); @endphp--}}
{{--                        @forelse($scopeItems as $item)--}}
{{--                            <div class="flex gap-2 scope-item">--}}
{{--                                <input type="text" name="scope_items[]" value="{{ $item }}"--}}
{{--                                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"--}}
{{--                                       placeholder="e.g. Up to 5 pages">--}}
{{--                                <button type="button" onclick="this.closest('.scope-item').remove()"--}}
{{--                                        class="p-2 text-gray-400 hover:text-red-500 transition">--}}
{{--                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>--}}
{{--                                    </svg>--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        @empty--}}
{{--                            <div class="flex gap-2 scope-item">--}}
{{--                                <input type="text" name="scope_items[]"--}}
{{--                                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"--}}
{{--                                       placeholder="e.g. Up to 5 pages">--}}
{{--                                <button type="button" onclick="this.closest('.scope-item').remove()"--}}
{{--                                        class="p-2 text-gray-400 hover:text-red-500 transition">--}}
{{--                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>--}}
{{--                                    </svg>--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        @endforelse--}}
{{--                    </div>--}}
{{--                    <button type="button" id="add-scope-item"--}}
{{--                            class="mt-3 inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 transition font-medium">--}}
{{--                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>--}}
{{--                        </svg>--}}
{{--                        Add item--}}
{{--                    </button>--}}
{{--                </div>--}}

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">

                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Scope Items
                    </h3>

                    <p class="text-xs text-gray-500 mb-3">
                        Add one item per line. You can paste a list directly.
                    </p>

                    <textarea
                        id="scope-textarea"
                        rows="8"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Example:
Homepage design
Up to 5 pages
Mobile responsive layout
SEO setup"
                    >{{ old('scope_items_text', isset($product) ? implode("\n", $product->scope_items ?? []) : '') }}</textarea>


                    {{-- Hidden fields sent to backend --}}
                    <div id="scope-hidden-inputs"></div>


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
                                @foreach(['once_off' => 'Once Off', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $val => $label)
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

    @push('scripts')
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

            // // Scope items
            // document.getElementById('add-scope-item').addEventListener('click', () => {
            //     const container = document.getElementById('scope-items');
            //     const div = document.createElement('div');
            //     div.className = 'flex gap-2 scope-item';
            //     div.innerHTML = `
            //     <input type="text" name="scope_items[]"
            //            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
            //            placeholder="e.g. Up to 5 pages">
            //     <button type="button" onclick="this.closest('.scope-item').remove()"
            //             class="p-2 text-gray-400 hover:text-red-500 transition">
            //         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            //             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            //         </svg>
            //     </button>`;
            //     container.appendChild(div);
            // });
            const scopeTextarea = document.getElementById('scope-textarea');
            const scopeHidden = document.getElementById('scope-hidden-inputs');


            function updateScopeItems()
            {
                scopeHidden.innerHTML = '';

                const lines = scopeTextarea.value
                    .split('\n')
                    .map(line => line.trim())
                    .filter(line => line.length > 0);


                lines.forEach(line => {

                    const input = document.createElement('input');

                    input.type = 'hidden';
                    input.name = 'scope_items[]';
                    input.value = line;

                    scopeHidden.appendChild(input);

                });
            }


            scopeTextarea.addEventListener('input', updateScopeItems);


            // Populate on edit page
            updateScopeItems();

            // Logo upload — preview + drag-and-drop
            const logoInput     = document.getElementById('logo-input');
            const logoPreview   = document.getElementById('logo-preview');
            const logoPlaceholder = document.getElementById('logo-placeholder');
            const logoFilename  = document.getElementById('logo-filename');
            const dropzone      = document.getElementById('logo-dropzone');

            function handleLogoFile(file) {
                if (! file) return;
                logoFilename.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                logoFilename.classList.remove('hidden');
                logoPlaceholder.classList.add('hidden');

                if (file.type.startsWith('image/') && file.type !== 'image/svg+xml') {
                    const reader = new FileReader();
                    reader.onload = e => {
                        logoPreview.src = e.target.result;
                        logoPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            }

            logoInput.addEventListener('change', () => handleLogoFile(logoInput.files[0]));

            dropzone.addEventListener('dragover', e => {
                e.preventDefault();
                dropzone.classList.add('border-blue-400', 'bg-blue-50/50');
            });
            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('border-blue-400', 'bg-blue-50/50');
            });
            dropzone.addEventListener('drop', e => {
                e.preventDefault();
                dropzone.classList.remove('border-blue-400', 'bg-blue-50/50');
                const file = e.dataTransfer.files[0];
                if (file) {
                    // Assign to the input so it goes with the form
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    logoInput.files = dt.files;
                    handleLogoFile(file);
                }
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
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const editors = document.querySelectorAll('.quill-editor');
                const quillInstances = [];

                // 1. Initialize all editors dynamically
                editors.forEach(editorEl => {
                    const quill = new Quill(editorEl, {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['clean']
                            ]
                        }
                    });

                    // Track the instance along with its relative element structure
                    quillInstances.push({
                        quill: quill,
                        container: editorEl
                    });
                });

                // 2. Intercept form submission to sync data
                if (quillInstances.length > 0) {
                    // Find the form containing the first editor instance
                    const form = quillInstances[0].container.closest('form');

                    if (form) {
                        form.addEventListener('submit', function () {
                            quillInstances.forEach(instance => {
                                // Find the wrapper parent containing both the editor and the hidden textarea
                                const wrapper = instance.container.closest('.rich-text-wrapper');
                                const hiddenInput = wrapper.querySelector('.hidden-quill-input');

                                // Sync html data
                                hiddenInput.value = instance.quill.root.innerHTML;
                            });
                        });
                    }
                }
            });
        </script>
    @endpush

</x-app-layout>

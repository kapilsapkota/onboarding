{{-- Basic Info --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
        Basic Information
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        {{-- Name --}}
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Company Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name', $company->name ?? '') }}"
                   placeholder="e.g. Fyre Digital"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200 @error('name') border-red-500 @enderror"
                   required>
            @error('name')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Slug --}}
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Slug
                <span class="text-gray-400 font-normal">(auto-generated if left blank)</span>
            </label>
            <input type="text" name="slug" id="slug-input" value="{{ old('slug', $company->slug ?? '') }}"
                   placeholder="e.g. fyre-digital"
                   class="block w-full px-3 py-2 text-sm font-mono border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200 @error('slug') border-red-500 @enderror">
            @error('slug')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $company->email ?? '') }}"
                   placeholder="hello@company.com"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200 @error('email') border-red-500 @enderror">
            @error('email')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Phone --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '') }}"
                   placeholder="+61 400 000 000"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200 @error('phone') border-red-500 @enderror">
            @error('phone')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

    </div>
</div>

{{-- Address --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
        Address
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        {{-- Street Address --}}
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Street Address</label>
            <input type="text" name="address" value="{{ old('address', $company->address ?? '') }}"
                   placeholder="123 Example St"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
        </div>

        {{-- City --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
            <input type="text" name="city" value="{{ old('city', $company->city ?? '') }}"
                   placeholder="Sydney"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
        </div>

        {{-- State --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">State / Province</label>
            <input type="text" name="state" value="{{ old('state', $company->state ?? '') }}"
                   placeholder="NSW"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
        </div>

        {{-- Postal Code --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Postal Code</label>
            <input type="text" name="postal_code" value="{{ old('postal_code', $company->postal_code ?? '') }}"
                   placeholder="2000"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
        </div>

        {{-- Country --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
            <input type="text" name="country" value="{{ old('country', $company->country ?? '') }}"
                   placeholder="Australia"
                   class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
        </div>

    </div>
</div>

{{-- Logo --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
        Logo
    </h3>

    @if(!empty($company->logo ?? null))
        <div class="mb-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Current Logo</p>
            <img src="{{ asset('images/' . $company->logo) }}"
                 alt="{{ $company->name }}"
                 class="h-16 w-auto object-contain rounded border border-gray-200 dark:border-gray-700 p-2 bg-white">
        </div>
    @endif

    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ !empty($company->logo ?? null) ? 'Replace Logo' : 'Upload Logo' }}
        </label>
        <input type="file" name="logo" accept="image/*"
               id="logo-input"
               class="block w-full text-sm text-gray-600 dark:text-gray-400
                      file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                      file:text-xs file:font-semibold file:uppercase file:tracking-wide
                      file:bg-gray-800 file:text-white dark:file:bg-gray-600
                      hover:file:bg-gray-700 transition">
        <p class="mt-1.5 text-xs text-gray-400">JPG, PNG, WebP, or SVG — max 2 MB</p>
        @error('logo')
        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror

        {{-- Preview --}}
        <div id="logo-preview" class="mt-3 hidden">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Preview</p>
            <img id="logo-preview-img" src="" alt="Logo preview"
                 class="h-16 w-auto object-contain rounded border border-gray-200 dark:border-gray-700 p-2 bg-white">
        </div>
    </div>
</div>

<script>
    // Auto-slug from name (only if slug is empty)
    const nameInput  = document.querySelector('[name="name"]');
    const slugInput  = document.getElementById('slug-input');
    let slugEdited   = slugInput.value.length > 0;

    slugInput.addEventListener('input', () => { slugEdited = true; });

    nameInput?.addEventListener('input', () => {
        if (slugEdited) return;
        slugInput.value = nameInput.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });

    // Logo preview
    document.getElementById('logo-input')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const preview = document.getElementById('logo-preview');
        const img     = document.getElementById('logo-preview-img');
        const reader  = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });
</script>

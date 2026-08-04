<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.categories.index') }}"
               class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 19l-7-7 7-7"/>
                </svg>
            </a>

            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                {{ isset($category) ? 'Edit Category' : 'New Category' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6 w-full mx-auto sm:px-6 lg:px-8">

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              enctype="multipart/form-data"
              action="{{ isset($category)
                    ? route('admin.categories.update',$category)
                    : route('admin.categories.store') }}">

            @csrf
            @isset($category)
                @method('PUT')
            @endisset

            <div class="space-y-6">

                {{-- Category Information --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">

                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                        Category Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                required
                                value="{{ old('name',$category->name ?? '') }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Slug
                            </label>

                            <input
                                type="text"
                                name="slug"
                                value="{{ old('slug',$category->slug ?? '') }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">

                            <p class="text-xs text-gray-400 mt-1">
                                Leave blank to generate automatically.
                            </p>
                        </div>

                        {{-- Color --}}
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Color
                            </label>

                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    name="color"
                                    value="{{ old('color',$category->color ?? '#2563eb') }}"
                                    class="h-11 w-16 rounded border">

                                <input
                                    type="text"
                                    name="color_text"
                                    value="{{ old('color',$category->color ?? '#2563eb') }}"
                                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            </div>
                        </div>

                        {{-- Icon --}}

                        <div class="md:col-span-2">

                            <x-image-upload
                                name="icon"
                                remove-name="remove_icon"
                                label="Category Icon"
                                :current="$category->icon ?? null"
                            />
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Sort Order
                            </label>

                            <input
                                type="number"
                                name="sort_order"
                                value="{{ old('sort_order',$category->sort_order ?? 0) }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>

                        {{-- Active --}}
                        <div class="flex items-center gap-3 pt-7">

                            <button
                                type="button"
                                id="toggle-active"
                                role="switch"
                                aria-checked="{{ old('is_active',$category->is_active ?? true) ? 'true' : 'false' }}"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition
                                {{ old('is_active',$category->is_active ?? true)
                                    ? 'bg-blue-600'
                                    : 'bg-gray-300' }}">

                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition
                                    {{ old('is_active',$category->is_active ?? true)
                                        ? 'translate-x-6'
                                        : 'translate-x-1' }}">
                                </span>

                            </button>

                            <input
                                type="hidden"
                                id="is-active-input"
                                name="is_active"
                                value="{{ old('is_active',$category->is_active ?? true) ? 1 : 0 }}">

                            <span>Active</span>

                        </div>

                    </div>

                </div>

                <div class="flex justify-between">

                    <a href="{{ route('admin.categories.index') }}"
                       class="text-gray-500 hover:text-gray-700">
                        Cancel
                    </a>

                    <button
                        class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">

                        {{ isset($category) ? 'Save Changes' : 'Create Category' }}

                    </button>

                </div>

            </div>

        </form>

    </div>

    @push('scripts')
        <script>

            // Color sync
            const picker = document.querySelector('input[type=color]');
            const text = document.querySelector('input[name=color_text]');

            picker.addEventListener('input', () => text.value = picker.value);

            text.addEventListener('input', () => picker.value = text.value);

            // Active switch
            const toggle = document.getElementById('toggle-active');
            const input = document.getElementById('is-active-input');

            toggle.addEventListener('click', () => {

                const active = toggle.getAttribute('aria-checked') === 'true';

                toggle.setAttribute('aria-checked', !active);

                input.value = active ? 0 : 1;

                toggle.classList.toggle('bg-blue-600');
                toggle.classList.toggle('bg-gray-300');

                toggle.querySelector('span').classList.toggle('translate-x-6');
                toggle.querySelector('span').classList.toggle('translate-x-1');

            });

        </script>
    @endpush

</x-app-layout>

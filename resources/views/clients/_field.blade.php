<div>
    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">{{ $label }}</label>
    <input type="{{ $type }}"
           name="{{ $name }}"
           value="{{ old($name) }}"
           placeholder="{{ $placeholder }}"
           class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
</div>

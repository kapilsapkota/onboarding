@props(['label' => '', 'required' => false, 'error' => null, 'hint' => null])

<div>
    @if($label)
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">{{ $label }}@if($required)<span class="text-[#F06927]">*</span>@endif</label>
    @endif
    {{ $slot }}
    @if($hint)
        <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @endif
</div>

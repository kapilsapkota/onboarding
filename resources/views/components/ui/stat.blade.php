@props(['label' => '', 'value' => '', 'sub' => null, 'accent' => 'slate', 'href' => null])

@php
$bar = match($accent) {
    'emerald' => 'bg-emerald-500',
    'rose' => 'bg-rose-500',
    'amber' => 'bg-amber-500',
    'sky' => 'bg-sky-500',
    'violet' => 'bg-violet-500',
    'orange' => 'bg-[#F06927]',
    default => 'bg-slate-300 dark:bg-slate-600',
};
@endphp

@if($href)
    <a href="{{ $href }}" class="group relative block overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800 dark:ring-gray-700">
        <div class="absolute inset-x-0 top-0 h-1 {{ $bar }}"></div>
        <div class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">{{ $label }}</div>
        <div class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ $value }}</div>
        @if($sub)
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $sub }}</div>
        @endif
    </a>
@else
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700">
        <div class="absolute inset-x-0 top-0 h-1 {{ $bar }}"></div>
        <div class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">{{ $label }}</div>
        <div class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ $value }}</div>
        @if($sub)
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $sub }}</div>
        @endif
    </div>
@endif

@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700']) }}>
    @if($title || isset($actions))
        <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
            <div>
                @if($title)
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>

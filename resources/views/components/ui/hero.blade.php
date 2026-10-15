@props(['eyebrow' => null, 'title' => '', 'subtitle' => null])

<div class="relative overflow-hidden rounded-3xl bg-slate-950 shadow-2xl">
    <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#F06927]/20 blur-3xl"></div>
    <div class="absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></div>
    <div class="relative flex flex-col gap-5 px-6 py-8 sm:px-10 lg:flex-row lg:items-center lg:justify-between">
        <div>
            @if($eyebrow)
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#F5A97B]">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
        @endif
    </div>
</div>

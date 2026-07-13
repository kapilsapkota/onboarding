<section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none px-10 py-10 flex flex-col">
    <h2 class="text-2xl font-bold text-orange-500 text-center mb-10">Our Partner Network</h2>

    <div class="flex-1 grid grid-cols-3 gap-y-10 gap-x-6 content-center items-center">
        @forelse(\App\Models\Company::get() as $i => $partner)
            @php
                $sizes = [
                    0 => 'h-10',
                    1 => 'h-18',
                    2 => 'h-14',
                    3 => 'h-24',
                    4 => 'h-20',
                    5 => 'h-10',
                    6 => 'h-16',
                    7 => 'h-10',
                    8 => 'h-10',
                    9 => 'h-20',
                ];
                $size = $sizes[$i] ?? 'h-12';
            @endphp
            <div class="flex items-center justify-center p-4">
                <img src="{{ asset('images/' . $partner['logo']) }}"
                     alt="{{ $partner['name'] }}"
                     class="{{ $size }} w-auto max-w-full object-contain"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="hidden text-sm font-semibold text-gray-700">{{ $partner['name'] }}</span>
            </div>
        @empty
            <p class="col-span-full text-center text-gray-400 text-sm">No partner logos configured.</p>
        @endforelse
    </div>
</section>

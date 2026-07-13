<section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none
            overflow-hidden flex flex-col h-full min-h-[500px]">
    <div class="flex-1 bg-white overflow-hidden rounded-xl relative w-full h-full">
        <div class="w-full h-full bg-white">
            <img src="{{ asset('images/img.png') }}"
                 alt="Cover"
                 class="w-full h-full object-cover"
                 onerror="this.style.display='none'">
        </div>
        {{-- Gradient backdrop bottom only from-black/70 via-black/30 --}}
        <div class="absolute inset-0 bg-gradient-to-t  to-transparent pointer-events-none"></div>

        <div class="absolute bottom-0 right-0 z-10 p-10 flex flex-col items-stretch gap-3 text-center w-[365px]">
            <h1 class="text-2xl font-bold leading-tight text-white">
                {{ $quote->project_title ?: config('quote.default_project_title') }}
            </h1>
            <div class="bg-white rounded-2xl p-5 w-full print:w-full h-52 flex items-center justify-center shadow-lg">
                @if($quote->logo_url)
                    <img src="{{ asset('storage/'.$quote->logo_url) }}"
                         alt="{{ $quote->client_name }}"
                         class="w-full h-full rounded-lg object-contain"
                         onerror="this.outerHTML='<span class=\'text-orange-500 font-bold text-sm text-center\'>{{ $quote->client_name }}</span>'">
                @else
                    <strong class="text-orange-500 font-bold text-sm text-center">{{ $quote->client_name }}</strong>
                @endif
            </div>
            <div>
                <div class="font-semibold text-xs text-white whitespace-nowrap">
                    By {{ $quote->prepared_by ?? 'Ali Taufeek' }} {{ now()->format('d-m-y') }}
                </div>
            </div>
        </div>
    </div>
</section>

@foreach(config('quote.images') ?? [] as $image)
    <!-- Isolated full-page container for the image with precise print margins -->
    <section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none overflow-hidden  print:break-after-page">

        <!-- Image box filling the entire page canvas area -->
        <div class="w-full h-full md:h-full bg-gray-50 rounded-lg overflow-hidden">
            <img src="{{ asset($image['image']) }}"
                 alt="{{ $image['placeholder'] }}"
                 class="inset-0 w-full h-full object-cover"
                 onerror="this.style.display='none'">
        </div>

    </section>
@endforeach

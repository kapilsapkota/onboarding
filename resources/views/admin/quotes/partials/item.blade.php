@foreach($quote->items as $item)
    <section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none overflow-hidden print:break-after-page">
        <div class="grid grid-cols-2 md:grid-cols-2 h-full">
            <div class="px-10 py-12 flex flex-col">
                <h2 class="text-2xl font-bold text-orange-500 mb-6">{{ $item->product_name }}</h2>

                <div class="font-semibold text-gray-800 mb-3">General Scope of Works</div>

                @if($item->scope_list)
                    <ul class="space-y-1.5 text-sm text-gray-800 mb-5">
                        @foreach($item->scope_list as $scope)
                            <li>- {{ $scope }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-auto">
                    <div class="font-semibold text-gray-900">
                        Total Price ${{ number_format($item->unit_price, 0) }} + GST
                    </div>
                    {{--                                <div class="text-xs text-gray-400 mt-1">--}}
                    {{--                                    {{ $item->frequency_label }}--}}
                    {{--                                    @if($item->hours)--}}
                    {{--                                        · {{ number_format($item->hours, 0) }} hrs @ ${{ number_format($item->hourly_rate, 0) }}/hr ex-GST--}}
                    {{--                                    @endif--}}
                    {{--                                </div>--}}
                    @if($item->notes)
                        <div class="text-xs text-yellow-700 bg-yellow-50 rounded px-3 py-2 mt-3">
                            <strong>Note:</strong> {{ $item->notes }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="relative min-h-[500px] md:min-h-full print:min-h-0">
                <img src="{{ asset('images/default.png') }}"
                     alt="{{ $item->product_name }}"
                     class="absolute inset-0 w-full h-full object-cover"
                     onerror="this.style.display='none'">
            </div>
        </div>
    </section>
@endforeach

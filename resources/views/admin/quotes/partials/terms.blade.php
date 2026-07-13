<section class="quote-page quote-page--flow bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none px-10 py-10">
    <table class="w-full">
        <thead>
        <tr>
            <th class="text-left pb-6 print:pt-10" style="padding-top: 40px;">
                <h2 class="text-2xl font-bold text-orange-500 mb-6 print:break-after-avoid sticky-print-header"
                    style="print-color-adjust: exact; -webkit-print-color-adjust: exact;">
                    Terms & Conditions
                </h2>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                @foreach($termsAndConditions as $index => $term)
                    <div class="term-section mb-6 print:break-inside-avoid">

                        <h3 class="text-base font-bold text-gray-800">
                            {{ $index + 1 }}. {{ $term['title'] }}
                        </h3>

                        @if(!empty($term['content']))
                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ $term['content'] }}
                            </p>
                        @endif

                        @if(!empty($term['subsections']))
                            @foreach($term['subsections'] as $sub)
                                <h4 class="font-semibold text-gray-700 mt-3">
                                    {{ $sub['title'] }}
                                </h4>

                                <p class="text-sm text-gray-600 leading-relaxed">
                                    {{ $sub['content'] }}
                                </p>
                            @endforeach
                        @endif

                        @if(!empty($term['points']))
                            <ul class="list-disc ml-5 text-sm text-gray-600 space-y-1">
                                @foreach($term['points'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($term['footer']))
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                                {{ $term['footer'] }}
                            </p>
                        @endif

                    </div>
                @endforeach
            </td>
        </tr>
        </tbody>
    </table>
</section>

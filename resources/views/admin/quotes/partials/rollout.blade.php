<section class="quote-page bg-white shadow-sm rounded-xl px-10 py-10 print:shadow-none print:rounded-none overflow-hidden flex flex-col">
    <h2 class="text-2xl font-bold text-orange-500 text-center mb-6">
        {{ $quote->overview_title ?? 'Our 3-Step Rollout Plan' }}
    </h2>

    <div class="flex-1 bg-gray-50 px-10 py-10">
        <div class="grid grid-cols-3 sm:grid-cols-3 gap-6">
            @foreach($stageColumns as $i => $names)
                <div>
                    <div class="rounded-full text-center text-white text-sm font-semibold py-2.5 px-4 mb-5 {{ $stageAccents[$i % 3] }}">
                        {{ $names['title'] }}
                    </div>
                    <ul class="space-y-3">
                        @foreach($names['items'] as $name)
                            <li class="flex items-start gap-2.5 text-sm text-gray-700">
                                            <span class="flex-shrink-0 w-4 h-4 rounded-full bg-blue-600 flex items-center justify-center mt-0.5">
                                                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                {{ $name }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

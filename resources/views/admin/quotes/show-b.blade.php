<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    {{ $quote->quote_number }}
                </h2>
                @php $badge = $quote->status_badge; @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                    {{ $badge['label'] }}
                </span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Status dropdown --}}
                <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $val => $label)
                            <option value="{{ $val }}" @selected($quote->status === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                {{-- Send --}}
                <form method="POST" action="{{ route('admin.quotes.send', $quote) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Quote
                    </button>
                </form>

                <a href="{{ route('admin.quotes.edit', $quote) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>

                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print / PDF
                </button>

                <a href="{{ route('admin.quotes.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    ← Quotes
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm print:hidden flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ================================================================
             QUOTE DOCUMENT
        ================================================================ --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden print:shadow-none print:rounded-none" id="quote-document">

            {{-- Quote header --}}
            <div class="px-8 py-8 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-start justify-between gap-6">
                    {{-- Client logo --}}
                    <div class="flex-1">
                        @if($quote->logo_url)
                            <img src="{{ $quote->logo_url }}"
                                 alt="{{ $quote->client_name }} logo"
                                 class="h-16 object-contain mb-4 max-w-xs"
                                 onerror="this.style.display='none'">
                        @endif
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $quote->client_name }}</h1>
                        @if($quote->contact_name)
                            <p class="text-gray-600 dark:text-gray-400 mt-0.5">Attn: {{ $quote->contact_name }}</p>
                        @endif
                        @if($quote->email)
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $quote->email }}</p>
                        @endif
                        @if($quote->mobile)
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $quote->mobile }}</p>
                        @endif
                        @if($quote->website)
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $quote->website }}</p>
                        @endif
                    </div>

                    {{-- Quote details --}}
                    <div class="text-right shrink-0">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">QUOTE</div>
                        <div class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                            <div><span class="font-medium text-gray-900 dark:text-gray-100">Quote #</span> {{ $quote->quote_number }}</div>
                            <div><span class="font-medium text-gray-900 dark:text-gray-100">Date</span> {{ $quote->created_at->format('d F Y') }}</div>
                            @if($quote->sent_at)
                                <div><span class="font-medium">Sent</span> {{ $quote->sent_at->format('d F Y') }}</div>
                            @endif
                        </div>
                        @if($quote->sharepoint_file_url)
                            <div class="mt-3 text-xs text-gray-400">
                                <a href="{{ $quote->sharepoint_file_url }}" target="_blank" class="hover:underline text-blue-500 print:hidden">
                                    📁 View in SharePoint
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Line items --}}
            <div class="px-8 py-6">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Services Quoted</h2>

                <div class="space-y-6">
                    @foreach($quote->items as $item)
                        <div class="border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
                            {{-- Item header --}}
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-5 py-3 flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">{{ $item->category_name }}</div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $item->product_name }}</div>
                                    @if($item->key_scope_keyword)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item->key_scope_keyword }}</div>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                        ${{ number_format($item->total_price, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-400">inc. GST</div>
                                    <span class="inline-flex mt-1 items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ $item->frequency_label }}
                                    </span>
                                </div>
                            </div>

                            {{-- Scope of works --}}
                            @if($item->scope_list)
                                <div class="px-5 py-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">Scope of Works</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
                                        @foreach($item->scope_list as $scope)
                                            <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <svg class="w-3.5 h-3.5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $scope }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Pricing breakdown row --}}
                            <div class="px-5 py-3 bg-gray-50/60 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>Ex-GST: <strong class="text-gray-700 dark:text-gray-300">${{ number_format($item->unit_price, 2) }}</strong></span>
                                <span>GST: <strong class="text-gray-700 dark:text-gray-300">${{ number_format($item->gst_amount, 2) }}</strong></span>
                                @if($item->hours)
                                    <span>Development: <strong class="text-gray-700 dark:text-gray-300">{{ number_format($item->hours, 0) }} hrs</strong>
                                          @ ${{ number_format($item->hourly_rate, 0) }}/hr ex-GST</span>
                                @endif
                            </div>

                            @if($item->notes)
                                <div class="px-5 py-2 bg-yellow-50 dark:bg-yellow-900/20 border-t border-yellow-100 dark:border-yellow-800/40 text-xs text-yellow-700 dark:text-yellow-300">
                                    <strong>Note:</strong> {{ $item->notes }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Totals --}}
            <div class="px-8 py-6 border-t border-gray-100 dark:border-gray-700">
                <div class="flex justify-end">
                    <div class="w-80 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal (ex. GST)</span>
                            <span>${{ number_format($quote->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>GST (10%)</span>
                            <span>${{ number_format($quote->gst_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center font-bold text-lg text-gray-900 dark:text-gray-100 pt-3 border-t-2 border-gray-200 dark:border-gray-600">
                            <span>Total (inc. GST)</span>
                            <span class="text-blue-600 dark:text-blue-400">${{ number_format($quote->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($quote->notes)
                <div class="px-8 py-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 print:hidden">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Internal Notes</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $quote->notes }}</p>
                </div>
            @endif

            {{-- Footer --}}
            <div class="px-8 py-5 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 dark:text-gray-500">
                <p>All prices are in Australian Dollars (AUD). GST of 10% has been applied. This quote is valid for 30 days from the date of issue.</p>
            </div>

        </div>

        {{-- Meta info (non-print) --}}
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 print:hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Created</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $quote->created_at->format('d M Y') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Last Updated</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $quote->updated_at->diffForHumans() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Items</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $quote->items->count() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Total (inc. GST)</div>
                <div class="text-sm font-bold text-blue-600 dark:text-blue-400">${{ number_format($quote->total, 2) }}</div>
            </div>
        </div>

    </div>

    @push('styles')
        <style>
            @media print {
                nav, header, .print\:hidden { display: none !important; }
                body { background: white !important; }
                #quote-document { max-width: 100%; }
            }
        </style>
    @endpush

</x-app-layout>

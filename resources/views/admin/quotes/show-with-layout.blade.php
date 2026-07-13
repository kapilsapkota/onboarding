<x-app-layout>
    <style>
        @media print {
            /* ── A4 Landscape = 297 × 210 mm ── */
            @page {
                size: A4 landscape;
                margin: 0;
            }

            /* Critical: tells browsers to actually print backgrounds, colours & images */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            nav, header, .print\:hidden {
                display: none !important;
            }

            body {
                background: white !important;
                margin: 0;
                padding: 0;
            }

            /* Strip outer wrapper screen-only spacing */
            .py-6          { padding-top: 0 !important; padding-bottom: 0 !important; }
            .max-w-5xl     { max-width: 100% !important; }
            .mx-auto       { margin-left: 0 !important; margin-right: 0 !important; }
            .sm\:px-6,
            .lg\:px-8      { padding-left: 0 !important; padding-right: 0 !important; }

            /* Zero out the space-y-6 gutters between sections */
            #quote-document > * + * {
                margin-top: 0 !important;
            }

            /* ── One A4 landscape sheet per .quote-page ── */
            .quote-page {
                width: 297mm !important;          /* ← was 210mm (portrait) */
                height: 210mm !important;         /* ← was 297mm (portrait) */
                max-width: 297mm !important;
                overflow: hidden !important;      /* clip anything that bleeds out */
                page-break-after: always !important;
                break-after: page !important;
                box-sizing: border-box !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
            }
            .quote-page--flow {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                page-break-after: auto !important;
                break-after: auto !important;
            }
            /* Keep individual T&C clauses together */
            .term-section {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            /* Keep heading attached to its clause body */
            h3 {
                break-after: avoid !important;
                page-break-after: avoid !important;
            }
            /* Repeat T&C heading on every printed page */
            .quote-page:last-child {
                display: table;
                width: 297mm;
                border-spacing: 0;
            }

            .sticky-print-header {
                display: table-header-group !important;
                padding-bottom: 24px !important;
            }
            .quote-page--flow + .quote-page {
                page-break-before: always !important;
                break-before: page !important;
            }
        }
    </style>

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

    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8 print:p-0 print:max-w-none print:m-0">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm print:hidden flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @php
            $partnerLogos = $quote->partner_logos ?: config('quote.default_partner_logos', []);
            $termsAndConditions = config('quote.default_terms');
            $stageColumns = config('quote.stage_columns');
            $stageColumns = collect($stageColumns);
            $stageAccents = ['bg-amber-400', 'bg-orange-500', 'bg-orange-700'];

            $termsText = $quote->terms_and_conditions ?: config('quote.default_terms');
        @endphp

        <div id="quote-document" class="space-y-6 print:space-y-0">

            @include('admin.quotes.partials.header')
            @include('admin.quotes.partials.partner')
            @include('admin.quotes.partials.rollout')
            @include('admin.quotes.partials.item', ['quote' => $quote])
            @include('admin.quotes.partials.images')

            {{-- ============================================================
                 TOTALS PAGE
            ============================================================ --}}
{{--            <section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none px-10 py-10 flex flex-col">--}}
{{--                <h2 class="text-2xl font-bold text-orange-500 mb-8">Summary</h2>--}}

{{--                <div class="flex-1 flex flex-col justify-center">--}}
{{--                    <div class="flex justify-end">--}}
{{--                        <div class="w-80 space-y-2 text-sm">--}}
{{--                            <div class="flex justify-between text-gray-600">--}}
{{--                                <span>Subtotal (ex. GST)</span>--}}
{{--                                <span>${{ number_format($quote->subtotal, 2) }}</span>--}}
{{--                            </div>--}}
{{--                            <div class="flex justify-between text-gray-600">--}}
{{--                                <span>GST (10%)</span>--}}
{{--                                <span>${{ number_format($quote->gst_amount, 2) }}</span>--}}
{{--                            </div>--}}
{{--                            <div class="flex justify-between items-center font-bold text-lg text-gray-900 pt-3 border-t-2 border-gray-200">--}}
{{--                                <span>Total (inc. GST)</span>--}}
{{--                                <span class="text-orange-500">${{ number_format($quote->total, 2) }}</span>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                @if($quote->notes)--}}
{{--                    <div class="mt-8 pt-6 border-t border-gray-100 print:hidden">--}}
{{--                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Internal Notes</div>--}}
{{--                        <p class="text-sm text-gray-600">{{ $quote->notes }}</p>--}}
{{--                    </div>--}}
{{--                @endif--}}
{{--            </section>--}}

            @include('admin.quotes.partials.terms', ['termsAndConditions' => $termsAndConditions])

            <section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none overflow-hidden print:break-after-page">
                <div class="flex-1 flex flex-col px-10 py-10">

                    {{-- Will be populated when client signs --}}
                    @if($quote->signed_at)
                        <div class="mb-8 inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-full self-start">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-medium text-green-700">Signed {{ $quote->signed_at->format('d M Y, h:i A') }}</span>
                        </div>
                    @endif

                    {{-- Company / Business Name --}}
                    <div class="mb-10">
                        <div class="flex items-end gap-3">
                <span class="text-xs font-bold uppercase tracking-widest text-orange-500 whitespace-nowrap pb-1">
                    Company / Business Name:
                </span>
                            @if($quote->signed_company_name)
                                <span class="flex-1 border-b border-dotted border-gray-400 pb-1 text-sm text-gray-800">
                        {{ $quote->signed_company_name }}
                    </span>
                            @else
                                <div class="flex-1 border-b border-dotted border-gray-400 pb-1">&nbsp;</div>
                            @endif
                        </div>
                    </div>

                    {{-- Authorised Person + Position --}}
                    <div class="grid grid-cols-2 gap-12 mb-10">
                        <div>
                            <div class="flex items-end gap-3">
                    <span class="text-xs font-bold uppercase tracking-widest text-orange-500 whitespace-nowrap pb-1">
                        Authorised Person:
                    </span>
                                @if($quote->signed_name)
                                    <span class="flex-1 border-b border-dotted border-gray-400 pb-1 text-sm text-gray-800">
                            {{ $quote->signed_name }}
                        </span>
                                @else
                                    <div class="flex-1 border-b border-dotted border-gray-400 pb-1">&nbsp;</div>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="flex items-end gap-3">
                    <span class="text-xs font-bold uppercase tracking-widest text-orange-500 whitespace-nowrap pb-1">
                        Position:
                    </span>
                                @if($quote->signed_position)
                                    <span class="flex-1 border-b border-dotted border-gray-400 pb-1 text-sm text-gray-800">
                            {{ $quote->signed_position }}
                        </span>
                                @else
                                    <div class="flex-1 border-b border-dotted border-gray-400 pb-1">&nbsp;</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Signature + Date --}}
                    <div class="grid grid-cols-2 gap-12 mb-10">
                        <div>
    <span class="text-xs font-bold uppercase tracking-widest text-orange-500 block mb-2">
        Signature:
    </span>
                            <div class="border border-dotted border-gray-300 rounded h-16 flex items-end p-2">
                                @if($quote->signature)
                                    <img src="{{ $quote->signature->signature_image }}" alt="Signature" class="max-h-12 max-w-full object-contain">
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="flex items-end gap-3 mt-14">
                    <span class="text-xs font-bold uppercase tracking-widest text-orange-500 whitespace-nowrap pb-1">
                        Date:
                    </span>
                                @if($quote->signed_at)
                                    <span class="flex-1 border-b border-dotted border-gray-400 pb-1 text-sm text-gray-800">
                            {{ $quote->signed_at->format('d / m / Y') }}
                        </span>
                                @else
                                    <div class="flex-1 border-b border-dotted border-gray-400 pb-1">&nbsp;</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Disclaimer --}}
                    <div class="mt-auto pt-10 text-center space-y-1 border-t border-gray-100">
                        <p class="text-xs font-semibold italic uppercase tracking-wide text-orange-500">
                            The person whose name and signature appears above warrants that they are authorised to enter
                        </p>
                        <p class="text-xs font-semibold italic uppercase tracking-wide text-orange-500">
                            into this agreement with {{ config('app.company_name', 'All in IT Solutions Pty Ltd') }} on behalf of the above company / business.
                        </p>
                    </div>

                </div>
            </section>

            <section class="quote-page bg-white shadow-sm rounded-xl print:shadow-none print:rounded-none overflow-hidden  print:break-after-page">

                <!-- Image box filling the entire page canvas area -->
                <div class="w-full h-full md:h-full bg-gray-50 rounded-lg overflow-hidden">
                    <img src="{{ asset('images/media/image67.jpg') }}"
                         alt="{{ "All in IT Solutions" }}"
                         class="inset-0 w-full h-full object-cover"
                         onerror="this.style.display='none'">
                </div>

            </section>
        </div>

        {{-- Meta info (non-print) --}}
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3 print:hidden">
            <div class="bg-white rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Created</div>
                <div class="text-sm font-medium text-gray-700">{{ $quote->created_at->format('d M Y') }}</div>
            </div>
            <div class="bg-white rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Last Updated</div>
                <div class="text-sm font-medium text-gray-700">{{ $quote->updated_at->diffForHumans() }}</div>
            </div>
            <div class="bg-white rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Items</div>
                <div class="text-sm font-medium text-gray-700">{{ $quote->items->count() }}</div>
            </div>
            <div class="bg-white rounded-lg p-3 shadow-sm text-center">
                <div class="text-xs text-gray-400 mb-0.5">Total (inc. GST)</div>
                <div class="text-sm font-bold text-orange-500">${{ number_format($quote->total, 2) }}</div>
            </div>
        </div>

    </div>
</x-app-layout>

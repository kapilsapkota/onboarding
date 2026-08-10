<x-app-layout>
    <x-slot name="title">
        {{ $quote->pdf_file_name }}
    </x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3 print:hidden">

            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    {{ $quote->quote_number }}
                </h2>

                @php
                    $badge = $quote->status_badge;
                @endphp

                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs
                         font-medium {{ $badge['class'] }}">
                {{ $badge['label'] }}
            </span>

                @if($quote->isLocked())
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full
                             text-xs font-medium bg-gray-100 text-gray-500
                             dark:bg-gray-700 dark:text-gray-400"
                          title="This quote can no longer be edited">
                    <svg class="w-3 h-3"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2h-1V7a5 5 0 00-10 0v3H6a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                    </svg>
                    Locked
                </span>
                @endif
            </div>


            <div class="flex items-center gap-2 flex-wrap">

                {{-- =========================================================
                     STATUS
                ========================================================== --}}
                @php
                    $canChangeStatus = $quote->canChangeStatus();
                @endphp

                @if($canChangeStatus)
                    <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                        @csrf
                        @method('PATCH')

                        <select
                            name="status"
                            onchange="this.form.submit()"
                            class="text-sm border-gray-300 dark:border-gray-600
                   dark:bg-gray-700 dark:text-gray-200 rounded-lg
                   focus:ring-2 focus:ring-indigo-500"
                        >
                            @if($quote->status === 'draft')
                                <option value="draft" @selected($quote->status === 'draft')>
                                    Draft
                                </option>

                                <option value="sent">
                                    Sent
                                </option>
                            @endif

                            @if($quote->status === 'sent')
                                <option value="sent" selected>
                                    Sent
                                </option>

                                <option value="accepted">
                                    Accepted
                                </option>

                                <option value="rejected">
                                    Rejected
                                </option>
                            @endif
                        </select>
                    </form>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                 text-xs font-semibold
                 bg-gray-100 text-gray-600
                 dark:bg-gray-700 dark:text-gray-300">

         {{ ucfirst($quote->status) }}
    </span>
                @endif


                {{-- =========================================================
                     SEND QUOTE
                ========================================================== --}}
                @if($quote->status === 'draft')
                    <button
                        type="button"
                        data-quote='@json($quote->sendModalData())'
                        onclick="openQuoteSendModalFromButton(this)"
                        class="inline-flex items-center gap-2 px-4 py-2
                           bg-green-600 hover:bg-green-700
                           text-white rounded-lg transition"
                    >
                        <svg class="w-4 h-4"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>

                        Send Quote
                    </button>
                @endif


                {{-- =========================================================
                     PRINT
                ========================================================== --}}
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2
                       bg-blue-600 text-white text-sm font-medium rounded-lg
                       hover:bg-blue-700 transition"
                >
                    <svg class="w-4 h-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H9v4a2 2 0 002 2zm6-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>

                    Print
                </button>


                {{-- =========================================================
                     PDF
                ========================================================== --}}
                <a
                    href="{{ route('admin.quotes.pdf', $quote) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2
                       bg-green-500 text-white text-sm font-medium rounded-lg
                       hover:bg-green-600 transition"
                >
                    <svg class="w-4 h-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A2 2 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>

                    PDF
                </a>


                {{-- =========================================================
                     EDIT
                ========================================================== --}}
                @if($quote->isEditable())

                    <a
                        href="{{ route('admin.quotes.edit', $quote) }}"
                        class="inline-flex items-center gap-2 px-4 py-2
                           bg-yellow-500 text-white text-sm font-medium rounded-lg
                           hover:bg-yellow-600 transition"
                    >
                        <svg class="w-4 h-4"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>

                        Edit
                    </a>

                @else

                    <span
                        class="inline-flex items-center gap-2 px-4 py-2
                           bg-gray-100 dark:bg-gray-700
                           text-gray-400 dark:text-gray-500
                           text-sm font-medium rounded-lg cursor-not-allowed"
                        title="This quote is locked and cannot be edited"
                    >
                    <svg class="w-4 h-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2h-1V7a5 5 0 00-10 0v3H6a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                    </svg>

                    Locked
                </span>

                @endif


                {{-- =========================================================
                     BACK
                ========================================================== --}}
                <a
                    href="{{ route('admin.quotes.index') }}"
                    class="text-sm text-gray-500 hover:text-gray-700
                       dark:text-gray-400 dark:hover:text-gray-200"
                >
                    ← Quotes
                </a>

            </div>
        </div>
    </x-slot>

        @include('admin.quotes.partials.quote-styles')
        <style>
        .cover-panel{
            width: 370px!important;
        }
        .quote-page {
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto 16px auto;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
        }

        /* Terms can grow beyond one A4 page */
        .quote-page.terms-page {
            height: auto !important;
            min-height: 210mm !important;
            max-height: none !important;
            overflow: visible !important;
            background: #fff !important;
        }
        /* ── Print ── */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                background: #fff !important;
            }

            main {
                background: #fff !important;
            }

            .quote-page {
                box-shadow: none;
            }
            .quote-page.terms-page {
                height: auto !important;
                min-height: 210mm !important;
                max-height: none !important;
                overflow: visible !important;
                background: #fff !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            header,
            nav,
            footer,
            .print\:hidden {
                display: none !important;
            }
            .quote-page.closing-page{
                width: 100vw !important;
                height: 100vh !important;
                page-break-before: always;
            }

            .bleed-wrap {
                position: fixed;   /* fills the physical page */
            }

            .bleed-img {
                width: 100% !important;
                height: 100% !important;
                object-fit: fill;
            }

        }
        .quote-page:last-child {
            page-break-after: auto;
        }

    </style>
        @include('admin.quotes.partials.quote-body')
    <div class="clear-both"></div>
    @include('admin.quotes.partials.send-modal')

    @include('admin.quotes.partials.delivery-status', ['quote' => $quote])

    <script>
        function openQuoteSendModalFromButton(button) {
            if (!button) {
                return;
            }

            const quoteJson = button.dataset.quote;

            if (!quoteJson) {
                console.error('Quote data is missing from button.');
                return;
            }

            try {
                const quote = JSON.parse(quoteJson);

                openSendQuoteModal(quote);

            } catch (error) {
                console.error('Unable to parse quote data:', error);
            }
        }
    </script>

</x-app-layout>

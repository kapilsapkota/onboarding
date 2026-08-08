<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3 print:hidden">
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
                <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $key => $label)
                            <option value="{{ $key }}" @selected($quote->status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                <button type="button" onclick="openSendQuoteModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg">
                    Send Quote
                </button>

                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>

                <a href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PDF
                </a>

                <a href="{{ route('admin.quotes.edit', $quote) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition">
                    Edit
                </a>

                <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">← Quotes</a>
            </div>
        </div>
    </x-slot>

    <style>
       .quote-page {
            width: 297mm;
            margin: 0 auto 16px auto;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
        }
        .cover-panel{
            width: 370px!important;
        }

        /* ── Print ── */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }

            #quote-viewer {
                background: white;
                padding: 0;
                width: 100%;
            }

            #quote-viewer .quote-page {
                width: 100%;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            #quote-viewer .quote-page:last-child {
                page-break-after: auto;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
        }
    </style>
        @include('admin.quotes.partials.quote-styles')
        @include('admin.quotes.partials.quote-body')

    @include('admin.quotes.partials.send-modal')

    <script>
        function openSendQuoteModal() {
            document.getElementById('sendQuoteModal').classList.remove('hidden');
        }
        function closeSendQuoteModal() {
            document.getElementById('sendQuoteModal').classList.add('hidden');
        }
        function toggleSendMethods() {
            const email = document.getElementById('send_email').checked;
            const sms   = document.getElementById('send_sms').checked;
            document.getElementById('emailSection').classList.toggle('hidden', !email);
            document.getElementById('smsSection').classList.toggle('hidden', !sms);
            if (!email && !sms) {
                document.getElementById('send_email').checked = true;
                document.getElementById('emailSection').classList.remove('hidden');
            }
        }
    </script>

</x-app-layout>

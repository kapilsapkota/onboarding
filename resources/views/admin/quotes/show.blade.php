<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">

            {{-- Title + badge --}}
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    {{ $quote->quote_number }}
                </h2>

                @php $badge = $quote->status_badge; @endphp

                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                    {{ $badge['label'] }}
                </span>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 flex-wrap">

                {{-- Status switcher --}}
                <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                    @csrf
                    @method('PATCH')
                    <select
                        name="status"
                        onchange="this.form.submit()"
                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $key => $label)
                            <option value="{{ $key }}" @selected($quote->status === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <form method="POST" action="{{ route('admin.quotes.send', $quote) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Send Quote
                    </button>
                </form>

                <a href="{{ route('admin.quotes.edit', $quote) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>

                <a href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15h6M9 11h6M12 3v6h6"/></svg>
                    PDF
                </a>

                <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">← Quotes</a>

            </div>
        </div>
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Success flash --}}
            @if(session('success'))
                <div class="flex items-center gap-2 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-700 dark:text-green-300 text-sm">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- PDF preview card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">

                <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200 text-sm">
                        PDF Preview
                    </h3>
                    <button
                        onclick="reloadPdf()"
                        class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 rounded-lg transition">
                        Refresh
                    </button>
                </div>

                {{-- Iframe fills the remaining viewport height minus header/toolbar --}}
                <div class="w-full" style="height: calc(100vh - 200px);">
                    <iframe
                        id="pdfFrame"
                        src="{{ route('admin.quotes.pdf', $quote) }}"
                        class="w-full h-full border-0"
                        loading="lazy"
                        title="Quote {{ $quote->quote_number }} PDF preview">
                        {{-- Fallback for browsers that block iframe PDFs --}}
                        <p class="p-6 text-gray-500 text-sm">
                            Your browser cannot display PDFs inline.
                            <a href="{{ route('admin.quotes.pdf', $quote) }}"
                               class="text-blue-600 underline" target="_blank">
                                Open the PDF directly
                            </a>.
                        </p>
                    </iframe>
                </div>

            </div>

        </div>
    </div>


    <script>
        function reloadPdf() {
            const frame = document.getElementById('pdfFrame');
            const base = frame.src.split('?')[0];
            frame.src = base + '?t=' + Date.now() + '#toolbar=1&view=FitH';
        }
    </script>

</x-app-layout>

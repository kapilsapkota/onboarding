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

                <button
                    type="button"
                    onclick="openSendQuoteModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg">
                    Send Quote
                </button>

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

    <div
        id="sendQuoteModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto">

        <div
            class="fixed inset-0 bg-black/50"
            onclick="closeSendQuoteModal()">
        </div>

        <div class="flex items-center justify-center min-h-screen p-6">

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl">

                <form method="POST" action="{{ route('admin.quotes.send', $quote) }}">
                    @csrf

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b dark:border-gray-700 flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold">
                                Send Quote
                            </h2>

                            <p class="text-sm text-gray-500 mt-1">
                                {{ $quote->quote_number }}
                            </p>
                        </div>

                        <button
                            type="button"
                            onclick="closeSendQuoteModal()"
                            class="text-gray-500 hover:text-gray-700">
                            ✕
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

                        {{-- Customer --}}
                        <div class="grid grid-cols-2 gap-6">

                            <div>
                                <h3 class="font-semibold mb-3">
                                    Client Details
                                </h3>

                                <div class="space-y-2 text-sm">

                                    <div>
                                        <span class="font-medium">Contact Name:</span>
                                        {{ $quote->contact_name }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Company:</span>
                                        {{ $quote->client_name }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Email:</span>
                                        {{ $quote->email }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Mobile:</span>
                                        {{ $quote->mobile }}
                                    </div>

                                </div>
                            </div>

                            <div>

                                <h3 class="font-semibold mb-3">
                                    Quote Details
                                </h3>

                                <div class="space-y-2 text-sm">

                                    <div>
                                        <span class="font-medium">Quote #</span>
                                        {{ $quote->quote_number }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Date</span>
                                        {{ optional($quote->created_at)->format('d/m/Y') }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Valid Until</span>
                                        {{ optional($quote->expires_at)->format('d/m/Y') ?? 'No Expiry' }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Total</span>

                                        ${{ number_format($quote->total,2) }}
                                    </div>

                                </div>

                            </div>

                        </div>

                        {{-- Items --}}
                        <div>

                            <h3 class="font-semibold mb-3">
                                Quote Items (X{{ count($quote->items) }})
                            </h3>

                            <div class="border rounded-lg overflow-hidden">

                                <div class="max-h-48 overflow-y-auto">
                                    <table class="w-full text-sm">

                                        <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                                        <tr>
                                            <th class="text-left px-4 py-2">Description</th>
                                            <th class="text-center px-4 py-2">Qty</th>
                                            <th class="text-right px-4 py-2">Price</th>
                                            <th class="text-right px-4 py-2">Total</th>
                                        </tr>
                                        </thead>

                                        <tbody>

                                        @foreach($quote->items as $item)

                                            <tr class="border-t">

                                                <td class="px-4 py-2">
                                                    {{ $item->product_name }}
                                                </td>

                                                <td class="px-4 py-2 text-center">
                                                    {{ $item->quantity }}
                                                </td>

                                                <td class="px-4 py-2 text-right">
                                                    ${{ number_format($item->unit_price,2) }}
                                                </td>

                                                <td class="px-4 py-2 text-right">
                                                    ${{ number_format($item->total_price,2) }}
                                                </td>

                                            </tr>

                                        @endforeach

                                        </tbody>

                                    </table>
                                </div>

                            </div>

                        </div>

                        <div>
                            <h3 class="font-semibold mb-3">Send Via</h3>

                            <div class="flex gap-6">

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="send_email"
                                        name="send_email"
                                        value="1"
                                        checked
                                        onchange="toggleSendMethods()">
                                    <span>Email</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="send_sms"
                                        name="send_sms"
                                        value="1"
                                        checked
                                        onchange="toggleSendMethods()">
                                    <span>SMS</span>
                                </label>

                            </div>

                        </div>

                        {{-- Email --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            {{-- Email --}}
                            <div id="emailSection" class="border rounded-lg p-4">

                                <h3 class="font-semibold mb-3">
                                    Email
                                </h3>

                                <div class="space-y-4">

                                    <div class="text-sm">
                                        <span class="font-medium">To:</span>
                                        {{ $quote->email }}
                                    </div>

                                    <div class="text-sm">
                                        <span class="font-medium">Subject:</span>
                                        Quotation {{ $quote->quote_number }}
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">
                                            Additional Message
                                            <span class="text-gray-500">(Optional)</span>
                                        </label>

                                        <textarea
                                            name="extra_message"
                                            rows="5"
                                            class="w-full rounded-lg border-gray-300"
                                            placeholder="Add any additional information you'd like to include..."></textarea>
                                    </div>

                                </div>

                            </div>

                            {{-- SMS --}}
                            <div id="smsSection" class="border rounded-lg p-4">

                                <h3 class="font-semibold mb-3">
                                    SMS
                                </h3>

                                <div class="space-y-4">

                                    <div class="text-sm">
                                        <span class="font-medium">Mobile:</span>
                                        {{ $quote->mobile }}
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">
                                            Additional Message
                                            <span class="text-gray-500">(Optional)</span>
                                        </label>

                                        <textarea
                                            name="extra_sms_message"
                                            rows="5"
                                            class="w-full rounded-lg border-gray-300"
                                            placeholder="Add an optional note..."></textarea>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-end gap-3">

                        <button
                            type="button"
                            onclick="closeSendQuoteModal()"
                            class="px-4 py-2 rounded-lg border">
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                            Send Quote
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <script>
        function reloadPdf() {
            const frame = document.getElementById('pdfFrame');
            const base = frame.src.split('?')[0];
            frame.src = base + '?t=' + Date.now() + '#toolbar=1&view=FitH';
        }
        function openSendQuoteModal(){
            document
                .getElementById('sendQuoteModal')
                .classList.remove('hidden');
        }

        function closeSendQuoteModal(){
            document
                .getElementById('sendQuoteModal')
                .classList.add('hidden');
        }

        function toggleSendMethods() {

            const email = document.getElementById('send_email').checked;
            const sms = document.getElementById('send_sms').checked;

            document.getElementById('emailSection')
                .classList.toggle('hidden', !email);

            document.getElementById('smsSection')
                .classList.toggle('hidden', !sms);

            // Prevent both being unchecked
            if (!email && !sms) {
                document.getElementById('send_email').checked = true;
                document.getElementById('emailSection').classList.remove('hidden');
            }
        }

    </script>

</x-app-layout>

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

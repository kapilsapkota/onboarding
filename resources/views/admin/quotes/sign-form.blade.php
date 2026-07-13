<x-app-layout>
    <!-- TOP ACTION HEADER BAR -->
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                        Review & Execute Contract
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                        Quote: {{ $quote->quote_number }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Please review the proposal document layout below before submitting your formal authorization details.</p>
            </div>

            <!-- Total Cost Summary Indicator -->
            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-1.5 border border-gray-200 dark:border-gray-600 rounded-lg text-right">
                <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-400 uppercase tracking-wider block">Total Amount</span>
                <span class="text-base font-bold text-gray-900 dark:text-gray-100">${{ number_format($quote->total, 2) }}</span>
            </div>
        </div>
    </x-slot>

    <!-- CONTAINER INTEGRATION WORKSPACE -->
    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- 1. FULL PAGE VIEWPORT PDF STREAM FRAME -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 h-[60vh] min-h-[450px]">
            <!-- Points securely to your backend route parameters -->
            <iframe src="{{ route('admin.quotes.pdf', $quote->id) }}" class="w-full h-full block" frameborder="0"></iframe>
        </div>

        <!-- 2. FORM INTERACTIVES AT THE BOTTOM PANEL -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            @if($quote->status === 'accepted')
                <!-- FIXED ACCEPTEED STATUS FRAME -->
                <div class="text-center py-6">
                    <div class="w-12 h-12 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Document Successfully Executed</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This agreement was verified and signed off by <strong>{{ $quote->client_name }}</strong> on {{ $quote->signed_at ? $quote->signed_at->format('d M Y') : now()->format('d M Y') }}.</p>
                </div>
            @elseif($quote->status === 'rejected')
                <!-- FIXED REJECTED STATUS FRAME -->
                <div class="text-center py-6">
                    <div class="w-12 h-12 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Proposal Terminated</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This quote parameter matrix file transfer is closed as rejected.</p>
                </div>
            @else
                <!-- ACTIVE INTERACTIVE WORKFLOW SECTIONS -->
                <form action="{{ route('admin.quotes.save-signature', $quote->id) }}" method="POST" id="signatureForm" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

                        <!-- LEFT CELL: Signee details inputs -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-1">Acceptance Authorization</h3>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Provide credentials to authenticate the underlying model rows.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Signee Full Name</label>
                                <input type="text"
                                       name="client_name"
                                       required
                                       placeholder="John Doe"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none text-sm transition shadow-xs">
                            </div>
                        </div>

                        <!-- RIGHT CELL: Vector Drawing Board Layout Context -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Digital Signature Pad</label>
                                <button type="button" id="clearButton" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">Clear Canvas</button>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden relative shadow-inner">
                                <canvas id="signaturePad" class="w-full h-36 touch-none block cursor-crosshair bg-white dark:bg-gray-900"></canvas>
                            </div>
                        </div>

                    </div>

                    <!-- Extraction transmission bridge -->
                    <input type="hidden" name="signature_data" id="signatureData">

                    <!-- Trigger buttons block layout elements -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                        <button type="submit" class="w-full md:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-md cursor-pointer transition transform active:scale-98">
                            Authorize & Execute Signed Proposal
                        </button>
                    </div>
                </form>
            @endif
        </div>

    </div>

    <!-- CODE SCRIPT BLOCK LOGIC -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@3.0.0-beta.3/dist/signature_pad.umd.min.js"></script>
    <script>
        window.addEventListener("load", function () {
            const canvas = document.getElementById('signaturePad');
            if (canvas) {
                const form = document.getElementById('signatureForm');
                const signatureDataInput = document.getElementById('signatureData');
                const clearButton = document.getElementById('clearButton');

                // Detect dark mode setting configuration to adjust tracking pen style colors dynamically
                const isDarkMode = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');
                const penColor = isDarkMode ? 'rgb(243, 244, 246)' : 'rgb(17, 24, 39)';

                const signaturePad = new SignaturePad(canvas, {
                    minWidth: 1.2,
                    maxWidth: 3.5,
                    penColor: penColor,
                    backgroundColor: 'rgba(255, 255, 255, 0)'
                });

                function resizeCanvas() {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    const currentData = signaturePad.toData();

                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);

                    signaturePad.clear();
                    signaturePad.fromData(currentData);
                }

                window.addEventListener("resize", resizeCanvas);
                resizeCanvas();

                clearButton.addEventListener('click', () => signaturePad.clear());

                form.addEventListener('submit', (e) => {
                    if (signaturePad.isEmpty()) {
                        e.preventDefault();
                        alert("Verification Error: Please sign your credentials on the signature card box layout before finishing processing workflows.");
                        return;
                    }
                    signatureDataInput.value = signaturePad.toDataURL('image/png');
                });
            }
        });
    </script>
</x-app-layout>

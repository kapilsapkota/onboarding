<x-signature-layout>
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
        <!-- 1. FULL PAGE VIEWPORT PDF STREAM FRAME WITH LOADER -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border
         border-gray-200 dark:border-gray-700 h-[60vh] min-h-[450px] relative"
             x-data="{ isLoading: true }">

            <!-- Loading Spinner Overlay -->
            <div x-show="isLoading"
                 class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xs z-10 flex flex-col items-center justify-center gap-3 transition-opacity duration-300">
                <svg class="animate-spin w-8 h-8 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 tracking-wider uppercase">Loading Proposal Document...</span>
            </div>

            <!-- Iframe with @load event handler to dismiss the spinner -->
            <iframe src="{{ route('admin.quotes.pdf', $quote->id) }}"
                    class="w-full h-full block"
                    frameborder="0"
                    loading="lazy"
                    @load="isLoading = false"></iframe>
        </div>

        <!-- BOTTOM STICKY / PANEL ACTION BAR -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700"
             x-data="{ showSignatureModal: false }">

            @if($quote->status === 'accepted')
                <div class="text-center py-4">
                    <div class="w-10 h-10 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-md font-bold text-gray-900 dark:text-gray-100">Document Successfully Executed</h3>
                    <p class="text-xs text-gray-500 mt-1">Signed by <strong>{{ $quote->client_name }}</strong> on {{ $quote->signed_at?->format('d M Y') ?? now()->format('d M Y') }}.</p>
                </div>
            @elseif($quote->status === 'rejected')
                <div class="text-center py-4">
                    <h3 class="text-md font-bold text-gray-900 dark:text-gray-100">Proposal Terminated</h3>
                </div>
            @else
                <!-- TRIGGER STATE -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Ready to proceed?</h3>
                        <p class="text-xs text-gray-500">Review the document above and click sign when you are ready to authorize.</p>
                    </div>
                    <button @click="showSignatureModal = true"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-md cursor-pointer transition">
                        Adopt & Sign Contract
                    </button>
                </div>

                <!-- MODAL OVERLAY WRAPPER -->
                <div x-show="showSignatureModal"
                     style="display: none;"
                     class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                     x-transition.opacity>

                    <!-- MODAL CARD CONTAINER -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-6"
                         @click.outside="showSignatureModal = false"
                         x-data="modalSignaturePad()"
                         x-init="initPad()">

                        <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-4">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-base">
                                Execute Proposal Agreement
                            </h3>

                            <button type="button"
                                    @click="showSignatureModal = false"
                                    class="text-gray-400 hover:text-gray-600">
                                ✕
                            </button>
                        </div>


                        <form action="{{ route('admin.quotes.save-signature', $quote->id) }}"
                              method="POST"
                              id="modalSignatureForm"
                              @submit.prevent="submitForm"
                              class="space-y-4">

                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Signee Full Name
                                </label>

                                <input type="text"
                                       name="client_name"
                                       required
                                       placeholder="John Doe"
                                       class="w-full px-4 py-2.5 border rounded-xl bg-white dark:bg-gray-700 text-sm">
                            </div>


                            <div class="space-y-2">

                                <div class="flex justify-between items-center">

                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Draw Your Signature
                                    </label>

                                    <button type="button"
                                            @click="clearPad"
                                            class="text-xs font-bold text-blue-600">
                                        Clear Canvas
                                    </button>

                                </div>


                                <div class="border rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden h-40">

                                    <canvas
                                        x-ref="modalCanvas"
                                        class="touch-none block cursor-crosshair bg-white dark:bg-gray-900">
                                    </canvas>

                                </div>

                            </div>


                            <template x-if="errorMessage">

                                <p class="text-xs text-red-600"
                                   x-text="errorMessage">
                                </p>

                            </template>


                            <input type="hidden"
                                   name="signature_data"
                                   x-model="signatureDataValue">


                            <div class="pt-4 border-t flex justify-end gap-3">

                                <button type="button"
                                        @click="showSignatureModal=false"
                                        class="px-4 py-2.5 bg-gray-100 rounded-xl">
                                    Cancel
                                </button>


                                <button type="submit"
                                        :disabled="isSubmitting"
                                        class="px-5 py-2.5 bg-blue-600 text-white rounded-xl">

                    <span x-show="isSubmitting">
                        Submitting...
                    </span>

                                    <span x-show="!isSubmitting">
                        Submit & Finalize
                    </span>

                                </button>

                            </div>

                        </form>

                    </div>

                </div>


                <script src="https://cdn.jsdelivr.net/npm/signature_pad@3.0.0-beta.3/dist/signature_pad.umd.min.js"></script>

                <script>
                    function modalSignaturePad() {
                        return {
                            signaturePad: null,
                            signatureDataValue: '',
                            errorMessage: '',
                            isSubmitting: false,

                            initPad() {
                                // Watch the inherited state directly
                                this.$watch('showSignatureModal', (value) => {
                                    if (value) {
                                        // Wait for modal transition to finish
                                        this.$nextTick(() => {
                                            setTimeout(() => {
                                                this.setupCanvas();
                                            }, 200);
                                        });
                                    }
                                });
                            },

                            setupCanvas() {
                                const canvas = this.$refs.modalCanvas;
                                if (!canvas) return;

                                const container = canvas.parentElement;
                                const ratio = Math.max(window.devicePixelRatio || 1, 1);

                                // Explicitly set backing store dimensions
                                canvas.width = container.clientWidth * ratio;
                                canvas.height = container.clientHeight * ratio;

                                // Explicitly set CSS rendering dimensions to prevent zero-width bugs
                                canvas.style.width = container.clientWidth + "px";
                                canvas.style.height = container.clientHeight + "px";

                                const ctx = canvas.getContext("2d");
                                ctx.scale(ratio, ratio);

                                if (!this.signaturePad) {
                                    this.signaturePad = new SignaturePad(canvas, {
                                        minWidth: 1.2,
                                        maxWidth: 3.5,
                                        penColor: document.documentElement.classList.contains('dark')
                                            ? 'rgb(243,244,246)'
                                            : 'rgb(17,24,39)',
                                        backgroundColor: 'rgba(255,255,255,0)'
                                    });
                                }

                                this.signaturePad.clear();
                            },

                            clearPad() {
                                if (this.signaturePad) {
                                    this.signaturePad.clear();
                                }
                                this.signatureDataValue = '';
                                this.errorMessage = '';
                            },

                            async submitForm() {
                                if (!this.signaturePad || this.signaturePad.isEmpty()) {
                                    this.errorMessage = "Please provide your digital signature before continuing.";
                                    return;
                                }

                                this.errorMessage = '';
                                this.signatureDataValue = this.signaturePad.toDataURL('image/png');
                                this.isSubmitting = true;

                                const formElement = document.getElementById('modalSignatureForm');
                                const formData = new FormData(formElement);

                                formData.append('signature_data', this.signatureDataValue);

                                try {
                                    const response = await fetch(formElement.action, {
                                        method: formElement.method || 'POST',
                                        body: formData,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json'
                                        }
                                    });

                                    // Check if response is ok (status in the range 200-299)
                                    if (response.ok) {
                                        this.isSubmitting = false;

                                        // Optional: Clear the pad for the next time it opens
                                        this.clearPad();

                                        // Close the modal using your Alpine state variable
                                        this.showSignatureModal = false;
                                    } else {
                                        // Parse backend error message if available
                                        const result = await response.json();
                                        this.isSubmitting = false;
                                        this.errorMessage = result.message || "Submission failed. Please try again.";
                                    }
                                } catch (error) {
                                    this.isSubmitting = false;
                                    this.errorMessage = "A network error occurred. Please check your connection.";
                                    console.error("Form submission error:", error);
                                }
                            }
                        }
                    }
                </script>
{{--                <script>--}}
{{--                    function modalSignaturePad() {--}}
{{--                        return {--}}
{{--                            signaturePad: null,--}}
{{--                            signatureDataValue: '',--}}
{{--                            errorMessage: '',--}}
{{--                            isSubmitting: false,--}}

{{--                            initPad() {--}}
{{--                                // Corrected: Watch the inherited state directly--}}
{{--                                this.$watch('showSignatureModal', (value) => {--}}
{{--                                    if (value) {--}}
{{--                                        // Wait for modal transition to finish--}}
{{--                                        this.$nextTick(() => {--}}
{{--                                            setTimeout(() => {--}}
{{--                                                this.setupCanvas();--}}
{{--                                            }, 200);--}}
{{--                                        });--}}
{{--                                    }--}}
{{--                                });--}}
{{--                            },--}}

{{--                            setupCanvas() {--}}
{{--                                const canvas = this.$refs.modalCanvas;--}}
{{--                                if (!canvas) return;--}}

{{--                                const container = canvas.parentElement;--}}
{{--                                const ratio = Math.max(window.devicePixelRatio || 1, 1);--}}

{{--                                // Explicitly set backing store dimensions--}}
{{--                                canvas.width = container.clientWidth * ratio;--}}
{{--                                canvas.height = container.clientHeight * ratio;--}}

{{--                                // Explicitly set CSS rendering dimensions to prevent zero-width bugs--}}
{{--                                canvas.style.width = container.clientWidth + "px";--}}
{{--                                canvas.style.height = container.clientHeight + "px";--}}

{{--                                const ctx = canvas.getContext("2d");--}}
{{--                                ctx.scale(ratio, ratio);--}}

{{--                                if (!this.signaturePad) {--}}
{{--                                    this.signaturePad = new SignaturePad(canvas, {--}}
{{--                                        minWidth: 1.2,--}}
{{--                                        maxWidth: 3.5,--}}
{{--                                        penColor: document.documentElement.classList.contains('dark')--}}
{{--                                            ? 'rgb(243,244,246)'--}}
{{--                                            : 'rgb(17,24,39)',--}}
{{--                                        backgroundColor: 'rgba(255,255,255,0)'--}}
{{--                                    });--}}
{{--                                }--}}

{{--                                this.signaturePad.clear();--}}
{{--                            },--}}

{{--                            clearPad() {--}}
{{--                                if (this.signaturePad) {--}}
{{--                                    this.signaturePad.clear();--}}
{{--                                }--}}
{{--                                this.signatureDataValue = '';--}}
{{--                                this.errorMessage = '';--}}
{{--                            },--}}

{{--                            submitForm() {--}}
{{--                                if (!this.signaturePad || this.signaturePad.isEmpty()) {--}}
{{--                                    this.errorMessage = "Please provide your digital signature before continuing.";--}}
{{--                                    return;--}}
{{--                                }--}}

{{--                                this.errorMessage = '';--}}
{{--                                this.signatureDataValue = this.signaturePad.toDataURL('image/png');--}}
{{--                                this.isSubmitting = true;--}}

{{--                                document.getElementById('modalSignatureForm').submit();--}}
{{--                            }--}}
{{--                        }--}}
{{--                    }--}}
{{--                </script>--}}
            @endif
        </div>
    </div>
</x-signature-layout>

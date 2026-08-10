<x-public-layout>

    {{-- Page header slot --}}
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">

            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                        Review &amp; Execute Contract
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs
                                 font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30
                                 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                        Quote: {{ $quote->quote_number }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Please review the proposal document below before submitting your formal
                    authorisation details.
                </p>
            </div>

            {{-- Total --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-1.5 border border-gray-200
                        dark:border-gray-600 rounded-lg text-right">
                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">
                    Total Amount
                </span>
                <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                    ${{ number_format($quote->total, 2) }}
                </span>
            </div>

        </div>
    </x-slot>

    {{-- =========================================================================
         BODY
    ========================================================================= --}}
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- =====================================================================
             1. PDF VIEWER
        ====================================================================== --}}
        <div
            class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden
                   border border-gray-200 dark:border-gray-700
                   h-[65vh] min-h-[480px] relative"
            x-data="{ isLoading: true }"
        >
            {{-- Loading overlay --}}
            <div
                x-show="isLoading"
                class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm
                       z-10 flex flex-col items-center justify-center gap-3"
            >
                <svg class="animate-spin w-8 h-8 text-blue-600 dark:text-blue-400"
                     fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962
                             7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300
                             tracking-wider uppercase">
                    Loading Proposal Document...
                </span>
            </div>

            {{-- PDF iframe — served from our public PDF endpoint --}}
            <iframe
                src="{{ $pdfUrl }}"
                class="w-full h-full block"
                frameborder="0"
                loading="lazy"
                @load="isLoading = false"
            ></iframe>
        </div>

        {{-- =====================================================================
             2. QUOTE SUMMARY (collapsible, customer reference)
        ====================================================================== --}}
        <div
            class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border
                   border-gray-200 dark:border-gray-700 overflow-hidden"
            x-data="{ open: false }"
        >
            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-6 py-4
                       text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition"
            >
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Quote Summary
                </span>
                <svg
                    class="h-4 w-4 text-gray-400 transition-transform"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open">
                <div class="border-t border-gray-100 dark:border-gray-700">

                    {{-- Meta --}}
                    <div class="grid grid-cols-2 gap-4 px-6 py-4
                                sm:grid-cols-4 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <p class="text-xs text-gray-400">Client</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $quote->client_name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Contact</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $quote->contact_name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Issued</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5">
                                {{ $quote->created_at->format('d M Y') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Valid Until</p>
                            <p class="text-sm font-semibold mt-0.5
                                      {{ $quote->expires_at?->isPast() ? 'text-red-600' : 'text-gray-800 dark:text-gray-100' }}">
                                {{ $quote->expires_at ? $quote->expires_at->format('d M Y') : '—' }}
                            </p>
                        </div>
                    </div>

                    {{-- Items --}}
                    @foreach($groupedItems as $group)
                        <div class="border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <p class="px-6 py-2 text-xs font-semibold text-gray-400 uppercase
                                      tracking-wider bg-gray-50 dark:bg-gray-700/40">
                                {{ $group['name'] }}
                            </p>
                            @foreach($group['items'] as $item)
                                @php
                                    $lineTotal = ($item->unit_price * $item->quantity) + $item->setup_fee;
                                @endphp

                                <div class="flex items-start justify-between px-6 py-3
                border-t border-gray-50 dark:border-gray-700/50
                first:border-0">

                                    <div class="min-w-0 flex-1 pr-4">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                            {{ $item->product_name }}
                                            <span class="text-gray-400 font-normal">
                    × {{ $item->quantity }}
                </span>
                                        </p>

                                        @if($item->scope_of_works)
                                            <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">
                                                {{ $item->scope_of_works }}
                                            </p>
                                        @endif

                                        <p class="text-xs text-gray-400 mt-1">
                                            ${{ number_format($item->unit_price, 2) }} each

                                            @if($item->setup_fee > 0)
                                                + ${{ number_format($item->setup_fee, 2) }} setup
                                            @endif
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                                            ${{ number_format($lineTotal, 2) }}
                                        </p>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    {{-- Totals --}}
                    <div class="px-6 py-4 space-y-1 bg-gray-50 dark:bg-gray-700/30">
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Subtotal (ex. GST)</span>
                            <span>${{ number_format($quote->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>GST (10%)</span>
                            <span>${{ number_format($quote->gst_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold
                                    text-gray-900 dark:text-white pt-2 border-t
                                    border-gray-200 dark:border-gray-600 mt-2">
                            <span>Total (inc. GST)</span>
                            <span>${{ number_format($quote->total, 2) }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- =====================================================================
             3. ACTION PANEL (sign / already signed / expired)
        ====================================================================== --}}
        <div
            class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border
                   border-gray-200 dark:border-gray-700"
            x-data="{ showSignatureModal: false }"
        >

            @if($alreadySigned || $quote->status === 'accepted')
                {{-- ── Already signed ─────────────────────────────────────── --}}
                <div class="text-center py-4">
                    <div class="w-10 h-10 bg-green-50 dark:bg-green-900/20 text-green-600
                                rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Document Successfully Executed
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        This proposal has been signed and accepted by
                        <strong>{{ $quote->client_name }}</strong>.
                    </p>
                </div>

            @elseif($quote->status === 'rejected')
                {{-- ── Rejected ────────────────────────────────────────────── --}}
                <div class="text-center py-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Proposal Terminated
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        This proposal is no longer active.
                    </p>
                </div>

            @elseif($isExpired)
                {{-- ── Expired ─────────────────────────────────────────────── --}}
                <div class="rounded-xl border border-amber-200 bg-amber-50
                            dark:border-amber-800 dark:bg-amber-900/20 px-5 py-4 text-center">
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        This quotation expired on {{ $quote->expires_at->format('d M Y') }}.
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                        Please contact us for an updated quote.
                    </p>
                </div>

            @else
                {{-- ── Ready to sign ───────────────────────────────────────── --}}

                {{-- Expiry notice --}}
                @if($quote->expires_at)
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50
                                dark:border-amber-800 dark:bg-amber-900/20 px-4 py-3">
                        <p class="text-xs text-amber-800 dark:text-amber-300">
                            This quotation is valid until
                            <strong>{{ $quote->expires_at->format('d M Y') }}</strong>.
                            After this date, prices may change.
                        </p>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            Ready to proceed?
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Review the document above and click Sign when you are ready
                            to authorise.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showSignatureModal = true"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white
                               font-bold text-sm rounded-xl shadow-md transition
                               cursor-pointer"
                    >
                        Adopt &amp; Sign Contract
                    </button>
                </div>

                {{-- ============================================================
                     SIGNATURE MODAL
                ============================================================= --}}
                <div
                    x-show="showSignatureModal"
                    style="display:none;"
                    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60
                           backdrop-blur-sm flex items-center justify-center p-4"
                    x-transition.opacity
                >
                    <div
                        class="bg-white dark:bg-gray-800 border border-gray-200
                               dark:border-gray-700 rounded-2xl max-w-lg w-full p-6
                               shadow-2xl space-y-6"
                        @click.outside="showSignatureModal = false"
                        x-data="publicSignaturePad()"
                        x-init="initPad()"
                    >

                        {{-- Modal header --}}
                        <div class="flex justify-between items-center border-b
                                    border-gray-100 dark:border-gray-700 pb-4">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-base">
                                Execute Proposal Agreement
                            </h3>
                            <button type="button"
                                    @click="showSignatureModal = false"
                                    class="text-gray-400 hover:text-gray-600
                                           dark:hover:text-gray-300 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Form --}}
                        <form
                            id="publicSignatureForm"
                            action="{{ $signatureUrl }}"
                            method="POST"
                            @submit.prevent="submitForm"
                            class="space-y-4"
                        >
                            @csrf

                            {{-- Authorised person --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700
                                             dark:text-gray-300 mb-1">
                                    Authorised Person
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="authorised_person"
                                    required
                                    placeholder="Full name"
                                    class="w-full px-4 py-2.5 border border-gray-300
                                           dark:border-gray-600 rounded-xl bg-white
                                           dark:bg-gray-700 text-sm text-gray-900
                                           dark:text-gray-100 focus:ring-2
                                           focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            {{-- Company name --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700
                                             dark:text-gray-300 mb-1">
                                    Company Name
                                    <span class="text-xs font-normal text-gray-400">
                                        (optional)
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    name="company_name"
                                    placeholder="{{ $quote->client_name }}"
                                    class="w-full px-4 py-2.5 border border-gray-300
                                           dark:border-gray-600 rounded-xl bg-white
                                           dark:bg-gray-700 text-sm text-gray-900
                                           dark:text-gray-100 focus:ring-2
                                           focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            {{-- Position --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700
                                             dark:text-gray-300 mb-1">
                                    Position / Title
                                    <span class="text-xs font-normal text-gray-400">
                                        (optional)
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    name="position"
                                    placeholder="e.g. Managing Director"
                                    class="w-full px-4 py-2.5 border border-gray-300
                                           dark:border-gray-600 rounded-xl bg-white
                                           dark:bg-gray-700 text-sm text-gray-900
                                           dark:text-gray-100 focus:ring-2
                                           focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            {{-- Signature canvas --}}
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <label class="block text-xs font-semibold text-gray-700
                                                 dark:text-gray-300">
                                        Draw Your Signature
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="clearPad"
                                        class="text-xs font-bold text-blue-600
                                               hover:text-blue-700 transition"
                                    >
                                        Clear Canvas
                                    </button>
                                </div>

                                <div class="border border-gray-300 dark:border-gray-600
                                            rounded-xl bg-gray-50 dark:bg-gray-900
                                            overflow-hidden h-40">
                                    <canvas
                                        x-ref="signCanvas"
                                        class="touch-none block cursor-crosshair
                                               bg-white dark:bg-gray-900 w-full h-full"
                                    ></canvas>
                                </div>
                            </div>

                            {{-- Error --}}
                            <template x-if="errorMessage">
                                <p class="text-xs text-red-600 dark:text-red-400"
                                   x-text="errorMessage">
                                </p>
                            </template>

                            {{-- Hidden signature data --}}
                            <input type="hidden" name="signature_data" x-model="signatureData">

                            {{-- Consent note --}}
                            <p class="text-xs text-gray-400 leading-relaxed">
                                By submitting this form you confirm you are authorised to
                                accept this proposal on behalf of your organisation and agree
                                to the terms within.
                            </p>

                            {{-- Actions --}}
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700
                                        flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="showSignatureModal = false"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700
                                           text-gray-700 dark:text-gray-200 rounded-xl
                                           text-sm font-medium hover:bg-gray-200
                                           dark:hover:bg-gray-600 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                                           text-white rounded-xl text-sm font-bold
                                           transition disabled:opacity-60
                                           disabled:cursor-not-allowed"
                                >
                                    <span x-show="isSubmitting">Submitting...</span>
                                    <span x-show="!isSubmitting">Submit &amp; Finalise</span>
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
                {{-- /modal --}}

            @endif

        </div>
        {{-- /action panel --}}

    </div>
    {{-- /body --}}
    <div
        id="signature-toast"
        class="fixed top-5 right-5 z-[9999] hidden
           w-[calc(100%-2rem)] max-w-sm"
    >
    </div>

</x-public-layout>

{{-- ============================================================================
     SignaturePad + Alpine component
     Loaded at end of body so it doesn't block the PDF iframe.
============================================================================= --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@3.0.0-beta.3/dist/signature_pad.umd.min.js"></script>

<script>
    function publicSignaturePad() {
        return {
            signaturePad: null,
            signatureData: '',
            errorMessage: '',
            isSubmitting: false,

            initPad() {
                this.$watch('showSignatureModal', (open) => {
                    if (open) {
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.setupCanvas();
                            }, 200);
                        });
                    }
                });
            },

            setupCanvas() {
                const canvas = this.$refs.signCanvas;

                if (!canvas) {
                    return;
                }

                const container = canvas.parentElement;
                const ratio = Math.max(
                    window.devicePixelRatio || 1,
                    1
                );

                const width = container.clientWidth;
                const height = container.clientHeight;

                canvas.width = width * ratio;
                canvas.height = height * ratio;

                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';

                const ctx = canvas.getContext('2d');

                ctx.setTransform(
                    ratio,
                    0,
                    0,
                    ratio,
                    0,
                    0
                );

                if (!this.signaturePad) {
                    this.signaturePad = new SignaturePad(
                        canvas,
                        {
                            minWidth: 1.2,
                            maxWidth: 3.5,

                            penColor:
                                document.documentElement
                                    .classList
                                    .contains('dark')
                                    ? 'rgb(243,244,246)'
                                    : 'rgb(17,24,39)',

                            backgroundColor:
                                'rgba(255,255,255,0)',
                        }
                    );
                }

                this.signaturePad.clear();
                this.signatureData = '';
                this.errorMessage = '';
            },

            clearPad() {
                this.signaturePad?.clear();

                this.signatureData = '';
                this.errorMessage = '';
            },

            async submitForm() {
                if (
                    !this.signaturePad ||
                    this.signaturePad.isEmpty()
                ) {
                    this.errorMessage =
                        'Please draw your signature before submitting.';

                    this.showToast(
                        'Please draw your signature before submitting.',
                        'error'
                    );

                    return;
                }

                this.errorMessage = '';

                this.signatureData =
                    this.signaturePad.toDataURL(
                        'image/png'
                    );

                this.isSubmitting = true;

                const form =
                    document.getElementById(
                        'publicSignatureForm'
                    );

                if (!form) {
                    this.isSubmitting = false;

                    this.showToast(
                        'Unable to submit the signature. Please refresh the page and try again.',
                        'error'
                    );

                    return;
                }

                const formData = new FormData(form);

                formData.set(
                    'signature_data',
                    this.signatureData
                );

                try {
                    const response = await fetch(
                        form.action,
                        {
                            method: 'POST',
                            body: formData,

                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'Accept':
                                    'application/json',
                            },
                        }
                    );

                    const result =
                        await response
                            .json()
                            .catch(() => ({}));

                    if (!response.ok) {
                        this.isSubmitting = false;

                        this.showToast(
                            result.message ||
                            'Submission failed. Please try again.',
                            'error'
                        );

                        return;
                    }

                    /*
                     * Success
                     */
                    this.isSubmitting = false;

                    this.showSignatureModal = false;

                    this.showToast(
                        result.message ||
                        'Your signature has been recorded and the proposal has been accepted.',
                        'success'
                    );

                    /*
                     * Allow the customer to see the
                     * confirmation before refreshing.
                     */
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);

                } catch (error) {
                    this.isSubmitting = false;

                    this.showToast(
                        'A network error occurred. Please check your connection and try again.',
                        'error'
                    );

                    console.error(
                        'Signature submission error:',
                        error
                    );
                }
            },

            showToast(
                message,
                type = 'success'
            ) {
                const existing =
                    document.getElementById(
                        'signature-toast'
                    );

                if (existing) {
                    existing.remove();
                }

                const success =
                    type === 'success';

                const toast =
                    document.createElement('div');

                toast.id =
                    'signature-toast';

                toast.className = `
                    fixed
                    top-5
                    right-5
                    z-[99999]
                    w-[calc(100%-2rem)]
                    max-w-md
                    opacity-0
                    translate-y-[-12px]
                    transition-all
                    duration-300
                `;

                toast.innerHTML = `
                    <div class="
                        flex
                        items-start
                        gap-3
                        rounded-2xl
                        border
                        bg-white
                        dark:bg-gray-800
                        px-5
                        py-4
                        shadow-2xl
                        ${
                    success
                        ? 'border-green-200 dark:border-green-800'
                        : 'border-red-200 dark:border-red-800'
                }
                    ">

                        <div class="
                            flex-shrink-0
                            w-10
                            h-10
                            rounded-full
                            flex
                            items-center
                            justify-center
                            ${
                    success
                        ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                }
                        ">
                            ${
                    success
                        ? `
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    `
                        : `
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    `
                }
                        </div>

                        <div class="min-w-0 flex-1">

                            <p class="
                                text-sm
                                font-bold
                                ${
                    success
                        ? 'text-green-800 dark:text-green-300'
                        : 'text-red-800 dark:text-red-300'
                }
                            ">
                                ${
                    success
                        ? 'Proposal Successfully Executed'
                        : 'Unable to Submit'
                }
                            </p>

                            <p class="
                                text-xs
                                mt-1
                                leading-relaxed
                                text-gray-600
                                dark:text-gray-300
                            ">
                                ${message}
                            </p>

                            ${
                    success
                        ? `
                                        <p class="
                                            text-[11px]
                                            mt-2
                                            text-gray-400
                                        ">
                                            Updating the document...
                                        </p>
                                    `
                        : ''
                }

                        </div>

                        <button
                            type="button"
                            class="
                                flex-shrink-0
                                text-gray-400
                                hover:text-gray-600
                                dark:hover:text-gray-200
                            "
                            onclick="
                                this.closest(
                                    '#signature-toast'
                                ).remove()
                            "
                            aria-label="Close"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>

                    </div>
                `;

                document.body.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.remove(
                        'opacity-0',
                        'translate-y-[-12px]'
                    );

                    toast.classList.add(
                        'opacity-100',
                        'translate-y-0'
                    );
                });
            }
        };
    }
</script>

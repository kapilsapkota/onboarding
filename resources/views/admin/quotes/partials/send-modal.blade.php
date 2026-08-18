    <div
        id="sendQuoteModal"
        class="fixed inset-0 z-[100] hidden"
        aria-hidden="true"
    >

        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
            onclick="closeSendQuoteModal()"
        ></div>


        {{-- Modal positioning --}}
        <div class="relative flex min-h-screen items-center justify-center p-3 sm:p-6">

            {{-- Modal --}}
            <div
                class="relative flex w-full max-w-5xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
                onclick="event.stopPropagation()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="sendQuoteModalTitle"
            >

                <form
                    id="sendQuoteForm"
                    method="POST"
                    data-action-template="{{ route('admin.quotes.send', ['quote' => '__QUOTE_ID__']) }}"
                    class="flex min-h-0 flex-1 flex-col"
                >

                    @csrf


                    {{-- =====================================================
                         HEADER
                    ====================================================== --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900 sm:px-6">

                        <div class="flex min-w-0 items-center gap-3">

                            {{-- Icon --}}
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">

                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>

                            </div>


                            <div class="min-w-0">

                                <h2
                                    id="sendQuoteModalTitle"
                                    class="truncate text-base font-semibold text-gray-900 dark:text-white sm:text-lg"
                                >
                                    Send Quote
                                </h2>

                                <p
                                    id="sendQuoteNumber"
                                    class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                                ></p>

                            </div>

                        </div>


                        {{-- Close --}}
                        <button
                            type="button"
                            onclick="closeSendQuoteModal()"
                            class="ml-3 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-200 dark:focus:ring-gray-600"
                            aria-label="Close"
                        >

                            <svg
                                class="h-5 w-5"
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


                    {{-- =====================================================
                         SCROLLABLE CONTENT
                         IMPORTANT:
                         This is the ONLY scrolling area.
                    ====================================================== --}}
                    <div class="min-h-0 flex-1 overflow-y-auto">

                        <div class="space-y-6 p-5 sm:p-6">


                            {{-- =================================================
                                 SUMMARY
                            ================================================== --}}
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">


                                {{-- Client --}}
                                <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">

                                    <div class="mb-4 flex items-center gap-2">

                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">

                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"
                                                />
                                            </svg>

                                        </div>

                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                            Client Details
                                        </h3>

                                    </div>


                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">

                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Contact
                                            </div>

                                            <div
                                                id="sendQuoteContactName"
                                                class="mt-0.5 truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                                            ></div>
                                        </div>


                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Company
                                            </div>

                                            <div
                                                id="sendQuoteClientName"
                                                class="mt-0.5 truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                                            ></div>
                                        </div>


                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Email
                                            </div>

                                            <div
                                                id="sendQuoteEmail"
                                                class="mt-0.5 truncate text-sm text-gray-700 dark:text-gray-300"
                                            ></div>
                                        </div>


                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Mobile
                                            </div>

                                            <div
                                                id="sendQuoteMobile"
                                                class="mt-0.5 truncate text-sm text-gray-700 dark:text-gray-300"
                                            ></div>
                                        </div>

                                    </div>

                                </div>


                                {{-- Quote --}}
                                <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">

                                    <div class="mb-4 flex items-center gap-2">

                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">

                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>

                                        </div>

                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                            Quote Details
                                        </h3>

                                    </div>


                                    <div class="grid grid-cols-2 gap-4">

                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Quote #
                                            </div>

                                            <div
                                                id="sendQuoteDetailsNumber"
                                                class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                            ></div>
                                        </div>


                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Date
                                            </div>

                                            <div
                                                id="sendQuoteDate"
                                                class="mt-0.5 text-sm text-gray-700 dark:text-gray-300"
                                            ></div>
                                        </div>


                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Valid Until
                                            </div>

                                            <div
                                                id="sendQuoteExpiry"
                                                class="mt-0.5 text-sm text-gray-700 dark:text-gray-300"
                                            ></div>
                                        </div>


                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Total
                                            </div>

                                            <div
                                                id="sendQuoteTotal"
                                                class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white"
                                            ></div>
                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- =================================================
                                 ITEMS
                            ================================================== --}}
                            <div>

                                <div class="mb-3 flex items-center justify-between">

                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        Quote Items
                                        <span
                                            id="sendQuoteItemCount"
                                            class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                        >
                                            0
                                        </span>
                                    </h3>

                                </div>


                                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">

                                    <div class="max-h-52 overflow-y-auto">

                                        <table class="w-full text-sm">

                                            <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">

                                            <tr>

                                                <th class="px-4 py-3 text-left font-medium">
                                                    Description
                                                </th>

                                                <th class="w-20 px-4 py-3 text-center font-medium">
                                                    Qty
                                                </th>

                                                <th class="w-28 px-4 py-3 text-right font-medium">
                                                    Price
                                                </th>

                                                <th class="w-28 px-4 py-3 text-right font-medium">
                                                    Total
                                                </th>

                                            </tr>

                                            </thead>


                                            <tbody
                                                id="sendQuoteItems"
                                                class="divide-y divide-gray-100 dark:divide-gray-700"
                                            ></tbody>

                                        </table>

                                    </div>

                                </div>

                            </div>


                            {{-- =================================================
                                 SEND METHOD
                            ================================================== --}}
                            <div>

                                <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Delivery Method
                                </h3>


                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">


                                    {{-- Email --}}
                                    <div>
                                        <label
                                            id="emailMethodCard"
                                            class="group relative flex cursor-pointer items-start gap-3 rounded-xl border-2 border-green-500 bg-green-50 p-4 transition dark:border-green-500 dark:bg-green-900/10"
                                        >

                                            <input
                                                type="checkbox"
                                                id="send_email"
                                                name="send_email"
                                                value="1"
                                                checked
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            >

                                            <div class="min-w-0">

                                                <div class="flex items-center gap-2">

                                                    <svg
                                                        class="h-5 w-5 text-green-600 dark:text-green-400"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.8"
                                                            d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                        />
                                                    </svg>

                                                    <span class="font-semibold text-gray-900 dark:text-white">
                                                    Email
                                                </span>

                                                </div>

                                                <p
                                                    id="sendEmailTo"
                                                    class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400"
                                                ></p>

                                            </div>

                                        </label>   
                                    </div>



                                    {{-- SMS --}}
                                    <label
                                        id="smsMethodCard"
                                        class="group relative flex cursor-pointer items-start gap-3 rounded-xl border-2 border-green-500 bg-green-50 p-4 transition dark:border-green-500 dark:bg-green-900/10"
                                    >

                                        <input
                                            type="checkbox"
                                            id="send_sms"
                                            name="send_sms"
                                            value="1"
                                            checked
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                        >

                                        <div class="min-w-0">

                                            <div class="flex items-center gap-2">

                                                <svg
                                                    class="h-5 w-5 text-green-600 dark:text-green-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M8 10h8M8 14h5m7-2a8 8 0 11-16 0c0 1.47.4 2.85 1.09 4.03L4 20l3.97-1.09A8 8 0 0020 12z"
                                                    />
                                                </svg>

                                                <span class="font-semibold text-gray-900 dark:text-white">
                                                    SMS
                                                </span>

                                            </div>

                                            <p
                                                id="sendSmsMobile"
                                                class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400"
                                            ></p>

                                        </div>

                                    </label>

                                </div>

                            </div>


                            {{-- =================================================
                                 MESSAGE SECTIONS
                            ================================================== --}}
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">


                                {{-- Email --}}
                                <div
                                    id="emailSection"
                                    class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
                                >

                                    <div class="mb-4 flex items-center gap-2">

                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">

                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                />
                                            </svg>

                                        </div>

                                        <div>

                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                Email Message
                                            </h3>

                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                The quote PDF will be attached.
                                            </p>

                                        </div>

                                    </div>


                                    <div class="space-y-3">

                                        <div>

                                            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                                                To
                                            </label>

                                            <div
                                                id="sendEmailRecipient"
                                                class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            ></div>

                                        </div>


                                        <div>

                                            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Subject
                                            </label>

                                            <div
                                                id="sendEmailSubject"
                                                class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            ></div>

                                        </div>


                                        <div>

                                            <label
                                                for="extraMessage"
                                                class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300"
                                            >
                                                Additional Message
                                                <span class="font-normal text-gray-400">
                                                    (optional)
                                                </span>
                                            </label>

                                            <textarea
                                                id="extraMessage"
                                                name="extra_message"
                                                rows="4"
                                                class="block w-full resize-y rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                                placeholder="Add a personal message..."
                                            ></textarea>

                                        </div>

                                    </div>

                                </div>


                                {{-- SMS --}}
                                <div
                                    id="smsSection"
                                    class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
                                >

                                    <div class="mb-4 flex items-center gap-2">

                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">

                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M8 10h8M8 14h5m7-2a8 8 0 11-16 0c0 1.47.4 2.85 1.09 4.03L4 20l3.97-1.09A8 8 0 0020 12z"
                                                />
                                            </svg>

                                        </div>

                                        <div>

                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                SMS Message
                                            </h3>

                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                The public quote link will be included.
                                            </p>

                                        </div>

                                    </div>


                                    <div class="space-y-3">

                                        <div>

                                            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Mobile
                                            </label>

                                            <div
                                                id="sendSmsRecipient"
                                                class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            ></div>

                                        </div>


                                        <div>

                                            <label
                                                for="extraSmsMessage"
                                                class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300"
                                            >
                                                Additional Message
                                                <span class="font-normal text-gray-400">
                                                    (optional)
                                                </span>
                                            </label>

                                            <textarea
                                                id="extraSmsMessage"
                                                name="extra_sms_message"
                                                rows="4"
                                                class="block w-full resize-y rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                                placeholder="Add a personal message..."
                                            ></textarea>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =====================================================
                         FOOTER
                         Always visible.
                    ====================================================== --}}
                    <div class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900 sm:px-6">

                        <div class="hidden text-xs text-gray-500 dark:text-gray-400 sm:block">
                            Your quote will be sent securely.
                        </div>

                        <div class="ml-auto flex items-center gap-2">

                            <button
                                type="button"
                                onclick="closeSendQuoteModal()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>


                            <button
                                id="sendQuoteSubmitButton"
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-gray-900"
                            >

                                <svg
                                    id="sendQuoteSubmitIcon"
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 10l19-7-7 19-2-8-10-4z"
                                    />
                                </svg>

                                <svg
                                    id="sendQuoteSubmitSpinner"
                                    class="hidden h-4 w-4 animate-spin"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    ></path>
                                </svg>

                                <span id="sendQuoteSubmitText">
                                    Send Quote
                                </span>

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <script>
        (function () {

            function getElement(id) {
                return document.getElementById(id);
            }


            function setText(id, value) {
                const element = getElement(id);

                if (element) {
                    element.textContent = value ?? '';
                }
            }


            function formatMoney(value) {

                const number = Number(value ?? 0);

                return '$' + number.toLocaleString('en-AU', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }


            function formatDate(value) {

                if (!value) {
                    return '';
                }

                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return date.toLocaleDateString('en-AU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }


            function updateMethodCard(card, enabled) {

                if (!card) {
                    return;
                }

                if (enabled) {

                    card.classList.add(
                        'border-green-500',
                        'bg-green-50',
                        'dark:bg-green-900/10'
                    );

                    card.classList.remove(
                        'border-gray-200',
                        'bg-white',
                        'dark:border-gray-700',
                        'dark:bg-gray-900'
                    );

                } else {

                    card.classList.remove(
                        'border-green-500',
                        'bg-green-50',
                        'dark:bg-green-900/10'
                    );

                    card.classList.add(
                        'border-gray-200',
                        'bg-white',
                        'dark:border-gray-700',
                        'dark:bg-gray-900'
                    );
                }
            }


            function updateSendMethods() {

                const emailCheckbox = getElement('send_email');
                const smsCheckbox = getElement('send_sms');

                const emailSection = getElement('emailSection');
                const smsSection = getElement('smsSection');

                const emailCard = getElement('emailMethodCard');
                const smsCard = getElement('smsMethodCard');

                if (!emailCheckbox || !smsCheckbox) {
                    return;
                }


                /*
                 * Never allow both to be disabled.
                 */
                if (!emailCheckbox.checked && !smsCheckbox.checked) {

                    emailCheckbox.checked = true;
                }


                const emailEnabled = emailCheckbox.checked;
                const smsEnabled = smsCheckbox.checked;


                if (emailSection) {
                    emailSection.classList.toggle(
                        'hidden',
                        !emailEnabled
                    );
                }

                if (smsSection) {
                    smsSection.classList.toggle(
                        'hidden',
                        !smsEnabled
                    );
                }


                updateMethodCard(
                    emailCard,
                    emailEnabled
                );

                updateMethodCard(
                    smsCard,
                    smsEnabled
                );
            }


            window.toggleSendMethods = updateSendMethods;


            /*
             * Open modal
             */
            window.openSendQuoteModal = function (quote) {

                if (!quote || !quote.id) {

                    console.error(
                        'Invalid quote supplied to openSendQuoteModal()',
                        quote
                    );

                    return;
                }


                const modal = getElement('sendQuoteModal');
                const form = getElement('sendQuoteForm');

                if (!modal || !form) {

                    console.error(
                        'Send quote modal was not found.'
                    );

                    return;
                }


                /*
                 * Form action
                 */
                const actionTemplate =
                    form.dataset.actionTemplate;

                if (actionTemplate) {

                    form.action = actionTemplate.replace(
                        '__QUOTE_ID__',
                        quote.id
                    );
                }


                /*
                 * Quote details
                 */
                setText(
                    'sendQuoteNumber',
                    quote.quote_number
                );

                setText(
                    'sendQuoteDetailsNumber',
                    quote.quote_number
                );

                setText(
                    'sendQuoteContactName',
                    quote.contact_name || '-'
                );

                setText(
                    'sendQuoteClientName',
                    quote.client_name || '-'
                );

                setText(
                    'sendQuoteEmail',
                    quote.email || 'No email'
                );

                setText(
                    'sendQuoteMobile',
                    quote.mobile || 'No mobile'
                );

                setText(
                    'sendEmailTo',
                    quote.email || 'No email address'
                );

                setText(
                    'sendEmailRecipient',
                    quote.email || 'No email address'
                );

                setText(
                    'sendSmsMobile',
                    quote.mobile || 'No mobile number'
                );

                setText(
                    'sendSmsRecipient',
                    quote.mobile || 'No mobile number'
                );

                setText(
                    'sendQuoteDate',
                    formatDate(quote.created_at)
                );

                setText(
                    'sendQuoteExpiry',
                    quote.expires_at
                        ? formatDate(quote.expires_at)
                        : 'No Expiry'
                );

                setText(
                    'sendQuoteTotal',
                    formatMoney(quote.total)
                );
                /*
                 * Email subject
                 */
                setText(
                    'sendEmailSubject',
                    (quote.email_subject ?? '')
                );


                /*
                 * Items
                 */
                const itemsContainer =
                    getElement('sendQuoteItems');

                const items = Array.isArray(quote.items)
                    ? quote.items
                    : [];


                setText(
                    'sendQuoteItemCount',
                    items.length
                );


                if (itemsContainer) {

                    itemsContainer.innerHTML = '';


                    if (items.length === 0) {

                        itemsContainer.innerHTML = `
                        <tr>
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                No quote items.
                            </td>
                        </tr>
                    `;

                    } else {

                        items.forEach(function (item) {

                            const row =
                                document.createElement('tr');

                            row.className =
                                'border-t border-gray-100 dark:border-gray-700';


                            const description =
                                document.createElement('td');

                            description.className =
                                'px-4 py-3 text-gray-700 dark:text-gray-300';

                            description.textContent =
                                item.product_name || '-';


                            const quantity =
                                document.createElement('td');

                            quantity.className =
                                'px-4 py-3 text-center text-gray-600 dark:text-gray-400';

                            quantity.textContent =
                                item.quantity ?? 1;


                            const price =
                                document.createElement('td');

                            price.className =
                                'px-4 py-3 text-right text-gray-600 dark:text-gray-400';

                            price.textContent =
                                formatMoney(item.unit_price);


                            const total =
                                document.createElement('td');

                            total.className =
                                'px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100';

                            total.textContent =
                                formatMoney(
                                    item.total_price ??
                                    (
                                        Number(item.quantity ?? 1) *
                                        Number(item.unit_price ?? 0)
                                    )
                                );


                            row.appendChild(description);
                            row.appendChild(quantity);
                            row.appendChild(price);
                            row.appendChild(total);

                            itemsContainer.appendChild(row);
                        });
                    }
                }


                /*
                 * Reset form fields
                 */
                const extraMessage =
                    getElement('extraMessage');

                const extraSmsMessage =
                    getElement('extraSmsMessage');

                if (extraMessage) {
                    extraMessage.value = '';
                }

                if (extraSmsMessage) {
                    extraSmsMessage.value = '';
                }


                /*
                 * Reset delivery methods
                 */
                const emailCheckbox =
                    getElement('send_email');

                const smsCheckbox =
                    getElement('send_sms');


                if (emailCheckbox) {
                    emailCheckbox.checked =
                        Boolean(quote.email);
                }

                if (smsCheckbox) {
                    smsCheckbox.checked =
                        Boolean(quote.mobile);
                }


                /*
                 * If neither contact method exists,
                 * default to email so the user can see
                 * the problem rather than having an empty modal.
                 */
                if (
                    emailCheckbox &&
                    smsCheckbox &&
                    !emailCheckbox.checked &&
                    !smsCheckbox.checked
                ) {
                    emailCheckbox.checked = true;
                }


                updateSendMethods();


                /*
                 * Reset submit button
                 */
                setSubmitLoading(false);


                /*
                 * Show modal
                 */
                modal.classList.remove('hidden');

                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.classList.add(
                    'overflow-hidden'
                );


                /*
                 * Focus first useful control
                 */
                setTimeout(function () {

                    const textarea =
                        getElement('extraMessage');

                    if (
                        textarea &&
                        !getElement('emailSection')
                            ?.classList.contains('hidden')
                    ) {
                        textarea.focus();
                    }

                }, 100);
            };


            /*
             * Close modal
             */
            window.closeSendQuoteModal = function () {

                const modal =
                    getElement('sendQuoteModal');

                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');

                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.classList.remove(
                    'overflow-hidden'
                );
            };


            /*
             * Submit loading state
             */
            function setSubmitLoading(loading) {

                const button =
                    getElement('sendQuoteSubmitButton');

                const icon =
                    getElement('sendQuoteSubmitIcon');

                const spinner =
                    getElement('sendQuoteSubmitSpinner');

                const text =
                    getElement('sendQuoteSubmitText');


                if (!button) {
                    return;
                }


                button.disabled = loading;


                if (icon) {
                    icon.classList.toggle(
                        'hidden',
                        loading
                    );
                }

                if (spinner) {
                    spinner.classList.toggle(
                        'hidden',
                        !loading
                    );
                }

                if (text) {
                    text.textContent =
                        loading
                            ? 'Sending...'
                            : 'Send Quote';
                }
            }


            /*
             * Form submit
             */
            document.addEventListener(
                'submit',
                function (event) {

                    if (
                        event.target &&
                        event.target.id === 'sendQuoteForm'
                    ) {

                        const email =
                            getElement('send_email');

                        const sms =
                            getElement('send_sms');


                        if (
                            email &&
                            sms &&
                            !email.checked &&
                            !sms.checked
                        ) {

                            event.preventDefault();

                            email.checked = true;

                            updateSendMethods();

                            return;
                        }


                        setSubmitLoading(true);
                    }
                }
            );


            /*
             * Delivery checkbox events
             */
            document.addEventListener(
                'change',
                function (event) {

                    if (
                        event.target &&
                        (
                            event.target.id === 'send_email' ||
                            event.target.id === 'send_sms'
                        )
                    ) {

                        updateSendMethods();
                    }
                }
            );


            /*
             * Escape key
             */
            document.addEventListener(
                'keydown',
                function (event) {

                    if (event.key !== 'Escape') {
                        return;
                    }

                    const modal =
                        getElement('sendQuoteModal');

                    if (
                        modal &&
                        !modal.classList.contains('hidden')
                    ) {
                        closeSendQuoteModal();
                    }
                }
            );

        })();
    </script>

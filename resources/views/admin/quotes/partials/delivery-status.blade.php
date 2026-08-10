@php
    $latestDelivery = $quote->latestDelivery;
@endphp

<div id="delivery-status-panel" class="mt-8 print:hidden">

    {{-- =====================================================================
         SECTION HEADER
    ====================================================================== --}}
    <div class="mb-4 flex items-center justify-between">

        <div class="flex items-center gap-2">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Delivery Status
            </h2>
            <span
                id="ds-processing-badge"
                class="hidden inline-flex items-center gap-1.5 rounded-full bg-blue-50
                       px-2.5 py-0.5 text-xs font-medium text-blue-700
                       dark:bg-blue-900/30 dark:text-blue-300"
            >
                <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Processing
            </span>
        </div>

        <button
            id="ds-refresh-btn"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200
                   bg-white px-3 py-1.5 text-xs font-medium text-gray-600
                   transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800
                   dark:text-gray-300 dark:hover:bg-gray-700"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9
                         m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>

    </div>

    {{-- =====================================================================
         FLASH MESSAGE
    ====================================================================== --}}
    <div
        id="ds-flash"
        class="hidden mb-4 rounded-lg border px-4 py-3 text-sm"
    ></div>

    {{-- =====================================================================
         PANEL BODY  (rendered by JS into this element)
    ====================================================================== --}}
    <div id="ds-body">

        @if(! $latestDelivery)

            {{-- No delivery yet --}}
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center
                        dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center
                            rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0
                                 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    This quote has not been sent yet.
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Use the Send Quote button to deliver it via email or SMS.
                </p>
            </div>

        @else

            {{-- Rendered immediately from server on first load.
                 JS will take over and keep this updated via polling. --}}
            <div id="ds-server-render">
                @include('admin.quotes.partials.delivery-status-card', [
                    'delivery' => $latestDelivery,
                ])
            </div>

        @endif

    </div>

</div>

{{-- =========================================================================
     SERVER-SIDE ROUTE DATA
     Passed to JS as JSON so no hardcoded URLs in script.
========================================================================= --}}
<script>
    (function () {
        'use strict';

        // ── Config ────────────────────────────────────────────────────────────────

        const POLL_INTERVAL_MS  = 3000;
        const TERMINAL_STATUSES = ['completed', 'partially_failed', 'failed', 'cancelled'];

        const QUOTE_ID   = {{ $quote->id }};
        const DELIVERY_ID = {{ $latestDelivery?->id ?? 'null' }};

        // Route templates — __DELIVERY__ and __ATTEMPT__ are replaced at runtime.
        const STATUS_URL_TPL = '{{ route('admin.quotes.deliveries.status', ['quote' => $quote->id, 'delivery' => '__DELIVERY__']) }}';
        const RETRY_URL_TPL  = '{{ route('admin.quotes.deliveries.attempts.retry', ['quote' => $quote->id, 'delivery' => '__DELIVERY__', 'attempt' => '__ATTEMPT__']) }}';
        const CSRF_TOKEN     = '{{ csrf_token() }}';

        // ── State ─────────────────────────────────────────────────────────────────

        let currentDeliveryId = DELIVERY_ID;
        let pollTimer         = null;
        let isPolling         = false;

        // ── DOM helpers ───────────────────────────────────────────────────────────

        const panel        = () => document.getElementById('ds-body');
        const flashEl      = () => document.getElementById('ds-flash');
        const badgeEl      = () => document.getElementById('ds-processing-badge');
        const refreshBtn   = () => document.getElementById('ds-refresh-btn');

        function showFlash(message, type) {
            const el = flashEl();
            if (!el) return;

            el.textContent = message;
            el.className = [
                'mb-4 rounded-lg border px-4 py-3 text-sm',
                type === 'success'
                    ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
                    : 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300',
            ].join(' ');
            el.classList.remove('hidden');

            // Auto-hide after 6 seconds.
            setTimeout(() => el.classList.add('hidden'), 6000);
        }

        function setProcessingBadge(visible) {
            const el = badgeEl();
            if (!el) return;
            el.classList.toggle('hidden', !visible);
        }

        // ── Rendering ─────────────────────────────────────────────────────────────

        function renderDelivery(delivery, attempts) {
            const container = panel();
            if (!container) return;

            const isActive = !TERMINAL_STATUSES.includes(delivery.status);

            // Overall status colours.
            const overallColor = {
                completed:        { dot: 'bg-green-500',  text: 'text-green-700 dark:text-green-400',  wrap: 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' },
                partially_failed: { dot: 'bg-yellow-500', text: 'text-yellow-700 dark:text-yellow-400', wrap: 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20' },
                failed:           { dot: 'bg-red-500',    text: 'text-red-700 dark:text-red-400',       wrap: 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' },
                cancelled:        { dot: 'bg-gray-400',   text: 'text-gray-600 dark:text-gray-400',    wrap: 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800' },
            }[delivery.status] ?? { dot: 'bg-blue-500', text: 'text-blue-700 dark:text-blue-400', wrap: 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' };

            // Group attempts by type, take highest attempt_number per type.
            const latestByType = {};
            for (const a of attempts) {
                if (!latestByType[a.type] || a.attempt_number > latestByType[a.type].attempt_number) {
                    latestByType[a.type] = a;
                }
            }

            // Operation display order.
            const TYPE_ORDER  = ['generate_pdf', 'generate_public_url', 'sharepoint_upload', 'email', 'sms'];
            const TYPE_LABELS = {
                generate_pdf:        'Generate PDF',
                generate_public_url: 'Generate Public Link',
                sharepoint_upload:   'SharePoint Upload',
                email:               'Send Email',
                sms:                 'Send SMS',
            };

            // Only include types that appear in the attempt list.
            const presentTypes = TYPE_ORDER.filter(t => latestByType[t]);

            // Build operation rows HTML.
            const operationRows = presentTypes.map((type, idx) => {
                const attempt  = latestByType[type];
                const isLast   = idx === presentTypes.length - 1;
                const status   = attempt.status;

                const statusConfig = {
                    succeeded:  { label: 'Completed',     textCls: 'text-green-600 dark:text-green-400',  iconBg: 'bg-green-100 dark:bg-green-900/30',  iconCls: 'text-green-600 dark:text-green-400',  iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' },
                    failed:     { label: 'Failed',        textCls: 'text-red-600 dark:text-red-400',      iconBg: 'bg-red-100 dark:bg-red-900/30',       iconCls: 'text-red-600 dark:text-red-400',      iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' },
                    skipped:    { label: 'Skipped',       textCls: 'text-gray-400 dark:text-gray-500',    iconBg: 'bg-gray-100 dark:bg-gray-700',        iconCls: 'text-gray-400 dark:text-gray-500',    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>' },
                    processing: { label: 'Processing...', textCls: 'text-blue-600 dark:text-blue-400',    iconBg: 'bg-blue-100 dark:bg-blue-900/30',     iconCls: 'text-blue-600 dark:text-blue-400 animate-spin', iconPath: '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>' },
                    pending:    { label: 'Pending',       textCls: 'text-gray-400 dark:text-gray-500',    iconBg: 'bg-gray-100 dark:bg-gray-700',        iconCls: 'text-gray-300 dark:text-gray-600',    iconPath: '<circle cx="12" cy="12" r="4" fill="currentColor"/>' },
                }[status] ?? { label: status, textCls: 'text-gray-400', iconBg: 'bg-gray-100', iconCls: 'text-gray-400', iconPath: '' };

                const attemptBadge = attempt.attempt_number > 1
                    ? `<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5
                               text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                       Attempt ${attempt.attempt_number}
                   </span>`
                    : '';

                const retryBtn = attempt.is_retryable
                    ? `<button
                       type="button"
                       data-retry-attempt="${attempt.id}"
                       data-delivery-id="${delivery.id}"
                       class="ds-retry-btn inline-flex items-center gap-1.5 rounded-lg
                              border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold
                              text-red-700 transition hover:bg-red-100
                              disabled:cursor-not-allowed disabled:opacity-60
                              dark:border-red-800 dark:bg-red-900/20 dark:text-red-400
                              dark:hover:bg-red-900/30"
                   >
                       <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                 d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9
                                    m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                       </svg>
                       Retry
                   </button>`
                    : '';

                const timestamps = [
                    attempt.started_at   ? `<span>Started ${formatDateTime(attempt.started_at)}</span>` : '',
                    attempt.completed_at ? `<span class="text-green-600 dark:text-green-500">Completed ${formatDateTime(attempt.completed_at)}</span>` : '',
                    attempt.failed_at    ? `<span class="text-red-500 dark:text-red-400">Failed ${formatDateTime(attempt.failed_at)}</span>` : '',
                ].filter(Boolean).join('');

                const errorBlock = (attempt.error_message && ['failed','skipped'].includes(status))
                    ? `<div class="${status === 'failed'
                        ? 'border border-red-100 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-400'
                        : 'border border-gray-100 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400'}
                    mt-2.5 rounded-lg px-3 py-2 text-xs leading-relaxed">
                    ${escHtml(attempt.error_message)}
                   </div>`
                    : '';

                return `
                <div class="px-5 py-4 ${isLast ? '' : 'border-b border-gray-100 dark:border-gray-700'}">
                    <div class="flex items-start gap-4">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center
                                    rounded-lg ${statusConfig.iconBg}">
                            <svg class="h-4 w-4 ${statusConfig.iconCls}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                ${statusConfig.iconPath}
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        ${escHtml(TYPE_LABELS[type] ?? type)}
                                    </span>
                                    ${attemptBadge}
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold ${statusConfig.textCls}">
                                        ${escHtml(statusConfig.label)}
                                    </span>
                                    ${retryBtn}
                                </div>
                            </div>
                            ${timestamps ? `<div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs
                                                        text-gray-400 dark:text-gray-500">${timestamps}</div>` : ''}
                            ${errorBlock}
                        </div>
                    </div>
                </div>
            `;
            }).join('');

            // Overall status card + operation rows.
            container.innerHTML = `
            <div class="mb-4 rounded-xl border ${overallColor.wrap} p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="h-2.5 w-2.5 rounded-full ${overallColor.dot}
                                    ${isActive ? 'animate-pulse' : ''}"></div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Overall Status
                            </p>
                            <p class="text-sm font-semibold ${overallColor.text}">
                                ${escHtml(delivery.status_label)}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 text-xs text-gray-400 dark:text-gray-500">
                        ${delivery.started_at
                ? `<span>Started <span class="font-medium text-gray-700 dark:text-gray-300">${formatDateTime(delivery.started_at)}</span></span>`
                : ''}
                        ${delivery.completed_at
                ? `<span>Completed <span class="font-medium text-gray-700 dark:text-gray-300">${formatDateTime(delivery.completed_at)}</span></span>`
                : ''}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white
                        dark:border-gray-700 dark:bg-gray-800">
                ${operationRows || '<p class="p-6 text-sm text-gray-400">No operations recorded yet.</p>'}
            </div>
        `;

            // Attach retry listeners AFTER the HTML is in the DOM.
            attachRetryListeners();

            // Show / hide processing badge.
            setProcessingBadge(isActive);
        }

        // ── Polling ───────────────────────────────────────────────────────────────

        function startPolling() {
            if (isPolling) return;
            isPolling  = true;
            pollTimer  = setInterval(fetchStatus, POLL_INTERVAL_MS);
        }

        function stopPolling() {
            isPolling = false;
            clearInterval(pollTimer);
            pollTimer = null;
            setProcessingBadge(false);
        }

        async function fetchStatus() {
            if (!currentDeliveryId) return;

            const url = STATUS_URL_TPL.replace('__DELIVERY__', currentDeliveryId);

            try {
                const res  = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!res.ok) {
                    console.error('Delivery status fetch failed:', res.status);
                    return;
                }

                const data = await res.json();

                renderDelivery(data.delivery, data.attempts);

                if (TERMINAL_STATUSES.includes(data.delivery.status)) {
                    stopPolling();
                }

            } catch (err) {
                console.error('Delivery status fetch error:', err);
            }
        }

        // ── Retry ─────────────────────────────────────────────────────────────────

        function attachRetryListeners() {
            document.querySelectorAll('.ds-retry-btn').forEach(btn => {
                // Guard against attaching duplicate listeners.
                if (btn.dataset.listenerAttached) return;
                btn.dataset.listenerAttached = '1';

                btn.addEventListener('click', async function () {
                    const attemptId  = this.dataset.retryAttemptId ?? this.dataset.retryAttempt;
                    const deliveryId = this.dataset.deliveryId;

                    if (!attemptId || !deliveryId) return;

                    // Spinner state.
                    this.disabled    = true;
                    this.innerHTML   = `
                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Retrying...
                `;

                    const url = RETRY_URL_TPL
                        .replace('__DELIVERY__', deliveryId)
                        .replace('__ATTEMPT__', attemptId);

                    try {
                        const res = await fetch(url, {
                            method:  'POST',
                            headers: {
                                'X-CSRF-TOKEN':    CSRF_TOKEN,
                                'Accept':          'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (res.ok) {
                            showFlash('Retry queued. Updating...', 'success');
                            currentDeliveryId = parseInt(deliveryId, 10);
                            // Fetch immediately then resume polling.
                            await fetchStatus();
                            startPolling();
                        } else {
                            showFlash('Unable to queue the retry. Please try again.', 'error');
                            // Re-enable button on failure.
                            await fetchStatus();
                        }

                    } catch (err) {
                        console.error('Retry error:', err);
                        showFlash('A network error occurred. Please try again.', 'error');
                        await fetchStatus();
                    }
                });
            });
        }

        // ── Utilities ─────────────────────────────────────────────────────────────

        function formatDateTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            return d.toLocaleString('en-AU', {
                day:    '2-digit',
                month:  'short',
                hour:   '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        }

        // Minimal HTML escaping — prevents XSS from server-supplied strings
        // rendered into innerHTML.
        function escHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g,  '&amp;')
                .replace(/</g,  '&lt;')
                .replace(/>/g,  '&gt;')
                .replace(/"/g,  '&quot;')
                .replace(/'/g,  '&#039;');
        }

        // ── Boot ──────────────────────────────────────────────────────────────────

        function boot() {
            // Attach retry listeners to the server-rendered HTML on first load.
            attachRetryListeners();

            // Manual refresh button.
            const btn = refreshBtn();
            if (btn) {
                btn.addEventListener('click', () => fetchStatus());
            }

            // Start polling if the delivery is in an active state.
            @if($latestDelivery && in_array($latestDelivery->status, ['pending', 'processing']))
            startPolling();
            @endif
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }

    })();
</script>

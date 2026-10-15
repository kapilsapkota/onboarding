<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Direct Debit Bulk Charges
            </h2>
            <a href="{{ route('admin.stripe.batches.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">
                View All Batches
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">

        {{-- Search + account filter --}}
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative w-full max-w-sm">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search by name or email..."
                    value="{{ $search }}"
                    autocomplete="off"
                    class="w-full pl-10 pr-4 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                >
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
            </div>
            <form method="GET" action="{{ route('admin.stripe.bulk-charge') }}" id="accountFilterForm" class="flex items-center gap-2">
                @if($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <select name="stripe_account" id="accountFilter" onchange="handleAccountChange(this.form)"
                        class="py-2 pl-3 pr-8 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="all" @selected($selectedAccount === 'all')>All accounts</option>
                    <option value="unassigned" @selected($selectedAccount === 'unassigned')>Unassigned (legacy)</option>
                    @foreach($stripeAccounts as $stripeAccount)
                        <option value="{{ $stripeAccount->id }}" @selected($selectedAccount instanceof \App\Models\StripeAccount && $selectedAccount->id === $stripeAccount->id)>
                            {{ $stripeAccount->display_name }}{{ $stripeAccount->company ? ' — '.$stripeAccount->company->name : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
            <label for="selectedFirstToggle" title="Pin selected customers to the top of the list"
                   class="flex items-center gap-1.5 px-3 py-2 border rounded-lg text-sm text-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 cursor-pointer select-none">
                <input type="checkbox" id="selectedFirstToggle" class="rounded" onchange="toggleSelectedFirst()">
                Selected first
            </label>
            <span id="searchSpinner" class="hidden text-xs text-gray-400 animate-pulse">Searching...</span>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.stripe.bulk-charge.confirm') }}" id="bulkChargeForm">
            @csrf
            @if($selectedAccount instanceof \App\Models\StripeAccount)
                <input type="hidden" name="stripe_account_id" value="{{ $selectedAccount->id }}">
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                        <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left">
                                <input type="checkbox" id="selectAll" class="rounded">
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Account</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment Method</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount ($)</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description / Reference</th>
                        </tr>
                        </thead>

                        <tbody id="customerTableBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @include('admin.stripe._bulk-charge-rows', ['customers' => $customers])
                        </tbody>

                    </table>
                </div>

                <div id="paginationWrapper" class="px-5 py-3">
                    {{ $customers->links() }}
                </div>
            </div>

            {{-- Summary bar --}}
            <div class="mt-4 flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Selected: <strong id="selectedCount">0</strong> customer(s) -
                    Total: <strong id="totalAmount">$0.00</strong>
                </div>
                <button
                    type="button"
                    id="reviewBtn"
                    disabled
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    onclick="openReviewModal()"
                >
                    Review Charges
                </button>
            </div>

        </form>
    </div>

    {{-- Review modal --}}
    <div id="reviewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="closeReviewModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Review Bulk Charge</h3>
                <button type="button" onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-4">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 pr-4">Customer</th>
                        <th class="pb-2 pr-4">Payment Method</th>
                        <th class="pb-2 pr-4">Account</th>
                        <th class="pb-2 pr-4">Description / Reference</th>
                        <th class="pb-2 text-right">Amount</th>
                    </tr>
                    </thead>
                    <tbody id="reviewTableBody" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                    <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-semibold">
                        <td class="pt-3 pr-4" id="reviewFooterCount"></td>
                        <td class="pt-3 pr-4" colspan="3"></td>
                        <td class="pt-3 text-right" id="reviewFooterTotal"></td>
                    </tr>
                    </tfoot>
                </table>
                <p id="reviewBatchNote" class="hidden mt-3 text-xs text-amber-600 dark:text-amber-400"></p>
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="closeReviewModal()" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Back</button>
                <button type="button" onclick="submitCharge()" class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Confirm & Create Charges</button>
            </div>
        </div>
    </div>

    <script>
        // Persists selections across search results, pagination and full page
        // reloads: customerId -> { pmId, name, pm, amount, description }
        const STORAGE_KEY = 'bulkChargeSelections.v1';
        const selections = new Map();
        // Typed field values kept even for unchecked rows so a typed amount is
        // never lost when the user searches, paginates or switches accounts.
        const drafts = new Map();

        let debounceTimer = null;

        const searchInput   = document.getElementById('searchInput');
        const spinner       = document.getElementById('searchSpinner');
        const tbody         = document.getElementById('customerTableBody');
        const pagination    = document.getElementById('paginationWrapper');
        const accountFilter = document.getElementById('accountFilter');
        const selectedFirstToggle = document.getElementById('selectedFirstToggle');

        // When on, selected rows are pinned to the top of the visible list.
        let selectedFirst = false;

        function persistState() {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                    selections: [...selections.entries()],
                    drafts: [...drafts.entries()],
                    selectedFirst,
                }));
            } catch (e) { /* storage unavailable - keep in-memory only */ }
        }

        function restoreFromStorage() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (! raw) return;
                const data = JSON.parse(raw);
                (data.selections || []).forEach(([id, val]) => selections.set(String(id), val));
                (data.drafts || []).forEach(([id, val]) => drafts.set(String(id), val));
                selectedFirst = data.selectedFirst === true;
                if (selectedFirstToggle) selectedFirstToggle.checked = selectedFirst;
            } catch (e) { /* ignore corrupt storage */ }
        }

        function clearPersistedState() {
            try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
        }

        // ----------------------------------------------------------------
        // Search (AJAX - selections/drafts survive tbody swaps)
        // ----------------------------------------------------------------

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => doSearch(1), 280);
        });

        function buildSearchUrl(page = 1) {
            const url = new URL(window.location.href);
            const q = searchInput.value.trim();
            if (q) {
                url.searchParams.set('search', q);
            } else {
                url.searchParams.delete('search');
            }
            if (accountFilter && accountFilter.value) {
                url.searchParams.set('stripe_account', accountFilter.value);
            }
            if (page && page > 1) {
                url.searchParams.set('page', page);
            } else {
                url.searchParams.delete('page');
            }
            return url;
        }

        async function doSearch(page = 1) {
            captureVisibleRows();
            spinner.classList.remove('hidden');

            const url = buildSearchUrl(page);

            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const contentType = res.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    const data = await res.json();
                    tbody.innerHTML = data.rows;
                    pagination.innerHTML = data.pagination;
                } else {
                    // Fallback: parse full-page HTML (old response shape).
                    const html = await res.text();
                    const doc  = new DOMParser().parseFromString(html, 'text/html');
                    const newBody = doc.getElementById('customerTableBody');
                    const newPagination = doc.getElementById('paginationWrapper');
                    if (newBody) tbody.innerHTML = newBody.innerHTML;
                    if (newPagination) pagination.innerHTML = newPagination.innerHTML;
                }

                window.history.replaceState({}, '', url);

                bindRows();
                restoreSelections();
                applySelectedFirstSort();
                updateSummary();
            } catch (e) {
                console.error('Search failed', e);
            } finally {
                spinner.classList.add('hidden');
            }
        }

        // Pagination links fetch via AJAX so in-memory + stored selections survive.
        pagination.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (! link) return;
            e.preventDefault();
            let page = 1;
            try {
                page = parseInt(new URL(link.href).searchParams.get('page') || '1', 10) || 1;
            } catch (err) { /* keep page 1 */ }
            doSearch(page);
        });

        function handleAccountChange(form) {
            captureVisibleRows();
            persistState();
            form.submit();
        }

        // ----------------------------------------------------------------
        // Row binding - call after any tbody swap
        // ----------------------------------------------------------------

        function bindRows() {
            document.querySelectorAll('.amount-input').forEach(input => {
                input.oninput = () => {
                    const tr = input.closest('tr');
                    const cb = tr.querySelector('.customer-checkbox');
                    cb.checked = parseFloat(input.value) > 0;
                    saveRowToSelections(tr);
                    applySelectedFirstSort();
                    updateSummary();
                };
            });

            document.querySelectorAll('.description-input').forEach(input => {
                input.oninput = () => {
                    saveRowToSelections(input.closest('tr'));
                };
            });

            document.querySelectorAll('.customer-checkbox').forEach(cb => {
                cb.onchange = () => {
                    saveRowToSelections(cb.closest('tr'));
                    applySelectedFirstSort();
                    updateSummary();
                };
            });

            // Assigned (not added) so re-binding after a search never stacks handlers.
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.onchange = function () {
                    document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = this.checked);
                    document.querySelectorAll('tr[data-customer-id]').forEach(tr => saveRowToSelections(tr));
                    applySelectedFirstSort();
                    updateSummary();
                };
            }
        }

        /** Saves typed values into drafts always; tracks checked+amount rows in selections. */
        function saveRowToSelections(tr) {
            const cb          = tr.querySelector('.customer-checkbox');
            const amountInput = tr.querySelector('.amount-input');
            const descInput   = tr.querySelector('.description-input');
            const customerId  = tr.dataset.customerId;

            if (! customerId) return;

            const amount      = (amountInput?.value ?? '').trim();
            const description = (descInput?.value ?? '').trim();

            if (amount !== '' || description !== '') {
                drafts.set(customerId, { amount, description });
            } else {
                drafts.delete(customerId);
            }

            if (cb.checked && parseFloat(amountInput?.value) > 0) {
                selections.set(customerId, {
                    pmId:        tr.dataset.pmId,
                    name:        tr.dataset.name,
                    pm:          tr.dataset.pm,
                    account:     tr.dataset.account || 'Legacy pool',
                    amount:      amountInput.value,
                    description: descInput?.value.trim() ?? '',
                });
            } else {
                selections.delete(customerId);
            }

            persistState();
        }

        /** Captures all currently visible rows before a tbody swap/navigation. */
        function captureVisibleRows() {
            document.querySelectorAll('tr[data-customer-id]').forEach(tr => saveRowToSelections(tr));
        }

        function restoreSelections() {
            document.querySelectorAll('tr[data-customer-id]').forEach(tr => {
                const id  = tr.dataset.customerId;
                const cb    = tr.querySelector('.customer-checkbox');
                const amt   = tr.querySelector('.amount-input');
                const desc  = tr.querySelector('.description-input');

                const draft = drafts.get(id);
                if (draft) {
                    if (amt && draft.amount !== undefined) amt.value = draft.amount;
                    if (desc && draft.description !== undefined) desc.value = draft.description;
                }

                const sel = selections.get(id);
                if (! sel) {
                    if (cb) cb.checked = false;
                    return;
                }

                if (cb) cb.checked = true;
                if (amt)  amt.value  = sel.amount;
                if (desc) desc.value = sel.description;
            });
        }

        // ----------------------------------------------------------------
        // Selected-first sorting + row highlighting
        // ----------------------------------------------------------------

        function toggleSelectedFirst() {
            selectedFirst = selectedFirstToggle.checked;
            persistState();
            applySelectedFirstSort();
        }

        /** Pins selected rows to the top of the currently visible list. Stable; keeps input focus. */
        function applySelectedFirstSort() {
            if (! selectedFirst) return;
            const rows = [...tbody.querySelectorAll('tr[data-customer-id]')];
            rows.sort((a, b) => {
                const aSel = selections.has(a.dataset.customerId) ? 0 : 1;
                const bSel = selections.has(b.dataset.customerId) ? 0 : 1;
                return aSel - bSel;
            });
            rows.forEach(row => tbody.appendChild(row));
        }

        function refreshRowHighlights() {
            document.querySelectorAll('tr[data-customer-id]').forEach(tr => {
                const isSel = selections.has(tr.dataset.customerId);
                tr.classList.toggle('bg-indigo-50', isSel);
                tr.classList.toggle('dark:bg-indigo-900/30', isSel);
            });
        }

        // ----------------------------------------------------------------
        // Summary bar
        // ----------------------------------------------------------------

        function updateSummary() {
            let total = 0;
            selections.forEach(s => { total += parseFloat(s.amount) || 0; });

            document.getElementById('selectedCount').textContent = selections.size;
            document.getElementById('totalAmount').textContent   = '$' + total.toFixed(2);
            document.getElementById('reviewBtn').disabled        = selections.size === 0 || total <= 0;
            refreshRowHighlights();
        }

        // ----------------------------------------------------------------
        // Review modal
        // ----------------------------------------------------------------

        function openReviewModal() {
            let total = 0;
            const rows = [];
            const accounts = new Set();

            selections.forEach((s, id) => {
                total += parseFloat(s.amount) || 0;
                accounts.add(s.account);
                rows.push(s);
            });

            document.getElementById('reviewTableBody').innerHTML = rows.map(r => `
                <tr>
                    <td class="py-2 pr-4 font-medium">${escHtml(r.name)}</td>
                    <td class="py-2 pr-4 font-mono text-xs text-gray-500">${escHtml(r.pm)}</td>
                    <td class="py-2 pr-4 text-xs text-gray-500">${escHtml(r.account)}</td>
                    <td class="py-2 pr-4 text-gray-500 text-xs">${r.description ? escHtml(r.description) : '<span class="italic text-gray-300">-</span>'}</td>
                    <td class="py-2 text-right">$${parseFloat(r.amount).toFixed(2)}</td>
                </tr>
            `).join('');

            document.getElementById('reviewFooterCount').textContent = rows.length + ' customer(s)';
            document.getElementById('reviewFooterTotal').textContent  = '$' + total.toFixed(2);

            const note = document.getElementById('reviewBatchNote');
            if (accounts.size > 1) {
                note.textContent = `Selection spans ${accounts.size} Stripe accounts — ${accounts.size} separate batches will be created (one per account).`;
                note.classList.remove('hidden');
            } else {
                note.classList.add('hidden');
            }

            document.getElementById('reviewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Builds hidden inputs from the selections Map and submits
        function submitCharge() {
            const form  = document.getElementById('bulkChargeForm');

            // Remove any previously injected hidden inputs
            form.querySelectorAll('.selection-input').forEach(el => el.remove());

            let i = 0;
            selections.forEach((s, customerId) => {
                const amountCents = Math.round(parseFloat(s.amount) * 100);
                [
                    ['stripe_customer_id',       customerId],
                    ['stripe_payment_method_id', s.pmId],
                    ['amount',                   amountCents],
                    ['description',              s.description],
                ].forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = `items[${i}][${name}]`;
                    input.value = value;
                    input.classList.add('selection-input');
                    form.appendChild(input);
                });
                i++;
            });

            clearPersistedState();
            form.submit();
        }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReviewModal(); });

        function escHtml(str) {
            return String(str).replace(/[&<>"']/g, m => ({
                '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
            })[m]);
        }

        // Initial bind on page load - restores amounts typed before any
        // search / pagination / account-filter navigation.
        restoreFromStorage();
        bindRows();
        restoreSelections();
        applySelectedFirstSort();
        updateSummary();
    </script>

</x-app-layout>

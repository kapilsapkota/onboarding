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

        {{-- Search --}}
        <div class="mb-4 flex items-center gap-3">
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
                        <th class="pb-2 pr-4">Description / Reference</th>
                        <th class="pb-2 text-right">Amount</th>
                    </tr>
                    </thead>
                    <tbody id="reviewTableBody" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                    <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-semibold">
                        <td class="pt-3 pr-4" id="reviewFooterCount"></td>
                        <td class="pt-3 pr-4" colspan="2"></td>
                        <td class="pt-3 text-right" id="reviewFooterTotal"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="closeReviewModal()" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Back</button>
                <button type="button" onclick="submitCharge()" class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Confirm & Create Charges</button>
            </div>
        </div>
    </div>

    <script>
        // Persists selections across search results: customerId -> { pmId, name, pm, amount, description }
        const selections = new Map();

        let debounceTimer = null;

        const searchInput   = document.getElementById('searchInput');
        const spinner       = document.getElementById('searchSpinner');
        const tbody         = document.getElementById('customerTableBody');
        const pagination    = document.getElementById('paginationWrapper');

        // ----------------------------------------------------------------
        // Search
        // ----------------------------------------------------------------

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(doSearch, 280);
        });

        async function doSearch() {
            spinner.classList.remove('hidden');

            const q   = searchInput.value.trim();
            const url = new URL(window.location.href);
            url.searchParams.set('search', q);
            url.searchParams.delete('page');

            try {
                const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc  = new DOMParser().parseFromString(html, 'text/html');

                tbody.innerHTML      = doc.getElementById('customerTableBody').innerHTML;
                pagination.innerHTML = doc.getElementById('paginationWrapper').innerHTML;

                bindRows();
                restoreSelections();
                updateSummary();
            } catch (e) {
                console.error('Search failed', e);
            } finally {
                spinner.classList.add('hidden');
            }
        }

        // ----------------------------------------------------------------
        // Row binding - call after any tbody swap
        // ----------------------------------------------------------------

        function bindRows() {
            document.querySelectorAll('.amount-input').forEach(input => {
                input.addEventListener('input', () => {
                    const cb = input.closest('tr').querySelector('.customer-checkbox');
                    cb.checked = parseFloat(input.value) > 0;
                    saveRowToSelections(input.closest('tr'));
                    updateSummary();
                });
            });

            document.querySelectorAll('.description-input').forEach(input => {
                input.addEventListener('input', () => {
                    saveRowToSelections(input.closest('tr'));
                });
            });

            document.querySelectorAll('.customer-checkbox').forEach(cb => {
                cb.addEventListener('change', () => {
                    saveRowToSelections(cb.closest('tr'));
                    updateSummary();
                });
            });

            document.getElementById('selectAll').addEventListener('change', function () {
                document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = this.checked);
                document.querySelectorAll('tr[data-customer-id]').forEach(tr => saveRowToSelections(tr));
                updateSummary();
            });
        }

        function saveRowToSelections(tr) {
            const cb          = tr.querySelector('.customer-checkbox');
            const amountInput = tr.querySelector('.amount-input');
            const descInput   = tr.querySelector('.description-input');
            const customerId  = tr.dataset.customerId;

            if (! customerId) return;

            if (cb.checked && parseFloat(amountInput?.value) > 0) {
                selections.set(customerId, {
                    pmId:        tr.dataset.pmId,
                    name:        tr.dataset.name,
                    pm:          tr.dataset.pm,
                    amount:      amountInput.value,
                    description: descInput?.value.trim() ?? '',
                });
            } else {
                selections.delete(customerId);
            }
        }

        function restoreSelections() {
            document.querySelectorAll('tr[data-customer-id]').forEach(tr => {
                const id  = tr.dataset.customerId;
                const sel = selections.get(id);
                if (! sel) return;

                const cb    = tr.querySelector('.customer-checkbox');
                const amt   = tr.querySelector('.amount-input');
                const desc  = tr.querySelector('.description-input');

                cb.checked  = true;
                if (amt)  amt.value  = sel.amount;
                if (desc) desc.value = sel.description;
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
        }

        // ----------------------------------------------------------------
        // Review modal
        // ----------------------------------------------------------------

        function openReviewModal() {
            let total = 0;
            const rows = [];

            selections.forEach((s, id) => {
                total += parseFloat(s.amount) || 0;
                rows.push(s);
            });

            document.getElementById('reviewTableBody').innerHTML = rows.map(r => `
                <tr>
                    <td class="py-2 pr-4 font-medium">${escHtml(r.name)}</td>
                    <td class="py-2 pr-4 font-mono text-xs text-gray-500">${escHtml(r.pm)}</td>
                    <td class="py-2 pr-4 text-gray-500 text-xs">${r.description ? escHtml(r.description) : '<span class="italic text-gray-300">-</span>'}</td>
                    <td class="py-2 text-right">$${parseFloat(r.amount).toFixed(2)}</td>
                </tr>
            `).join('');

            document.getElementById('reviewFooterCount').textContent = rows.length + ' customer(s)';
            document.getElementById('reviewFooterTotal').textContent  = '$' + total.toFixed(2);

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

            form.submit();
        }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReviewModal(); });

        function escHtml(str) {
            return String(str).replace(/[&<>"']/g, m => ({
                '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
            })[m]);
        }

        // Initial bind on page load
        bindRows();
    </script>

</x-app-layout>

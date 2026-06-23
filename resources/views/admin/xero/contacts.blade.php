<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Xero Contact Reconciliation
        </h2>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">
        <x-alert></x-alert>

        {{-- HEADER --}}
        <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">{{ $xeroTenant->tenant_name }}</h3>
                    <p class="text-sm text-gray-500">Match Xero contacts with your internal customers</p>
                    <p class="text-sm text-gray-400 mt-1">
                        Last synced:
                        @if($xeroTenant->last_contact_synced_at)
                            {{ $xeroTenant->last_contact_synced_at->diffForHumans() }}
                            <span>({{ $xeroTenant->last_contact_synced_at->format('d/m/Y H:i') }})</span>
                        @else
                            Never synced
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.xero.contacts.sync', ['tenant' => $xeroTenant]) }}">
                        @csrf
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Sync Contacts
                        </button>
                    </form>
                    <button id="bulkMatchBtn"
                            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-40"
                            disabled>
                        Bulk Match Selected
                    </button>
                    <a href="{{ route('admin.xero.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        Back
                    </a>
                </div>
            </div>
        </div>

        {{-- SUMMARY PILLS / FILTERS --}}
        @php
            $allContacts     = collect($contacts);
            $totalCount      = $allContacts->count();
            $matchedCount    = $allContacts->filter(fn($c) => $c['stored']?->isMatched())->count();
            $autoCount       = $allContacts->filter(fn($c) => ($c['match']['score'] ?? 0) >= 95 && !$c['stored']?->isMatched())->count();
            $possibleCount   = $allContacts->filter(fn($c) => ($c['match']['score'] ?? 0) >= 60 && ($c['match']['score'] ?? 0) < 95 && !$c['stored']?->isMatched())->count();
            $unmatchedCount  = $allContacts->filter(fn($c) => ($c['match']['score'] ?? 0) < 60 && !$c['stored']?->isMatched())->count();

            $activeFilter = request('filter', 'all');

            // Apply filter
            $filtered = match($activeFilter) {
                'matched'   => $allContacts->filter(fn($c) => $c['stored']?->isMatched()),
                'auto'      => $allContacts->filter(fn($c) => ($c['match']['score'] ?? 0) >= 95 && !$c['stored']?->isMatched()),
                'possible'  => $allContacts->filter(fn($c) => ($c['match']['score'] ?? 0) >= 60 && ($c['match']['score'] ?? 0) < 95 && !$c['stored']?->isMatched()),
                'unmatched' => $allContacts->filter(fn($c) => ($c['match']['score'] ?? 0) < 60 && !$c['stored']?->isMatched()),
                default     => $allContacts,
            };

            // Paginate manually
            $perPage     = 25;
            $currentPage = max(1, (int) request('page', 1));
            $total       = $filtered->count();
            $lastPage    = max(1, (int) ceil($total / $perPage));
            $currentPage = min($currentPage, $lastPage);
            $pageItems   = $filtered->values()->slice(($currentPage - 1) * $perPage, $perPage);

            $filterUrl = fn($f, $p = 1) => request()->fullUrlWithQuery(['filter' => $f, 'page' => $p]);
        @endphp

        <div class="flex flex-wrap gap-2 mb-4 text-sm">
            <a href="{{ $filterUrl('all') }}"
               class="px-3 py-1 rounded-full font-medium border transition-colors
                      {{ $activeFilter === 'all' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-500' }}">
                All ({{ $totalCount }})
            </a>
            <a href="{{ $filterUrl('matched') }}"
               class="px-3 py-1 rounded-full font-medium border transition-colors
                      {{ $activeFilter === 'matched' ? 'bg-green-700 text-white border-green-700' : 'bg-green-50 text-green-700 border-green-200 hover:border-green-400' }}">
                ✓ Matched ({{ $matchedCount }})
            </a>
            <a href="{{ $filterUrl('auto') }}"
               class="px-3 py-1 rounded-full font-medium border transition-colors
                      {{ $activeFilter === 'auto' ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:border-yellow-400' }}">
                ⚡ Auto-ready ({{ $autoCount }})
            </a>
            <a href="{{ $filterUrl('possible') }}"
               class="px-3 py-1 rounded-full font-medium border transition-colors
                      {{ $activeFilter === 'possible' ? 'bg-orange-500 text-white border-orange-500' : 'bg-orange-50 text-orange-700 border-orange-200 hover:border-orange-400' }}">
                ~ Possible ({{ $possibleCount }})
            </a>
            <a href="{{ $filterUrl('unmatched') }}"
               class="px-3 py-1 rounded-full font-medium border transition-colors
                      {{ $activeFilter === 'unmatched' ? 'bg-gray-600 text-white border-gray-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-400' }}">
                No match ({{ $unmatchedCount }})
            </a>
        </div>

        {{-- TABLE --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="p-3 w-8">
                        <input type="checkbox" id="selectAll" class="rounded">
                    </th>
                    <th class="p-3">Xero Contact</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Phone</th>
                    <th class="p-3">Match Customer</th>
                    <th class="p-3">Confidence</th>
                    <th class="p-3">Actions</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($pageItems as $contact)
                    @php
                        $stored      = $contact['stored'];
                        $matchResult = $contact['match'];
                        $score       = $matchResult['score'] ?? 0;
                        $suggestion  = $matchResult['client'] ?? null;

                        $isConfirmed = $stored?->isMatched();
                        $isAuto      = !$isConfirmed && $score >= 95;
                        $isPossible  = !$isConfirmed && $score >= 60 && $score < 95;

                        $preselectedId = $isConfirmed ? $stored->client_id : $suggestion?->id;

                        $rowBg = $isConfirmed ? 'bg-green-50 dark:bg-green-900/10'
                               : ($isAuto     ? 'bg-yellow-50 dark:bg-yellow-900/10'
                               :                '');
                    @endphp

                    <tr class="{{ $rowBg }} transition-colors" data-row="{{ $contact['ContactID'] }}">

                        <td class="p-3">
                            <input type="checkbox"
                                   class="contact-checkbox rounded"
                                   value="{{ $contact['ContactID'] }}"
                                {{ $isConfirmed ? 'disabled' : '' }}>
                        </td>

                        <td class="p-3 font-medium">
                            {{ $contact['Name'] ?? '-' }}
                            @if($isConfirmed)
                                <span class="ml-1 text-xs font-normal text-gray-400">
                                    via {{ $stored->match_method === 'manual' ? 'manual' : 'auto' }}
                                </span>
                            @endif
                        </td>

                        <td class="p-3 text-gray-500">{{ $contact['EmailAddress'] ?? '-' }}</td>

                        <td class="p-3 text-gray-500">{{ $contact['Phones'][0]['PhoneNumber'] ?? '-' }}</td>

                        {{-- DROPDOWN — always visible --}}
                        <td class="p-3">
                            <select class="w-full border rounded p-1 text-sm customer-select focus:ring-2 focus:ring-blue-300 focus:outline-none"
                                    data-contact="{{ $contact['ContactID'] }}">
                                <option value="">— Select customer —</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        {{ $preselectedId === $customer->id ? 'selected' : '' }}>
                                        {{ $customer->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- CONFIDENCE — badge is clickable to auto-select suggestion --}}
                        <td class="p-3">
                            @if($isConfirmed)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full font-medium">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                                    Confirmed
                                </span>
                            @elseif($isAuto && $suggestion)
                                <button type="button"
                                        class="use-suggestion-btn px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full font-medium hover:bg-yellow-200 transition-colors"
                                        data-contact="{{ $contact['ContactID'] }}"
                                        data-customer="{{ $suggestion->id }}"
                                        title="Click to select {{ $suggestion->company_name }} in the dropdown">
                                    ⚡ Auto ({{ round($score) }}%) — use it
                                </button>
                            @elseif($isPossible && $suggestion)
                                <button type="button"
                                        class="use-suggestion-btn px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded-full font-medium hover:bg-orange-200 transition-colors"
                                        data-contact="{{ $contact['ContactID'] }}"
                                        data-customer="{{ $suggestion->id }}"
                                        title="Click to select {{ $suggestion->company_name }} in the dropdown">
                                    ~ Possible ({{ round($score) }}%) — use it
                                </button>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-500 rounded-full">
                                    No match
                                </span>
                            @endif
                        </td>

                        {{-- ACTIONS --}}
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <button class="assign-btn px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-40"
                                        data-contact="{{ $contact['ContactID'] }}">
                                    {{ $isConfirmed ? 'Reassign' : 'Assign' }}
                                </button>
                                @if($isConfirmed)
                                    <button class="unassign-btn text-xs text-red-500 hover:text-red-700 underline"
                                            data-contact="{{ $contact['ContactID'] }}"
                                            data-xero-contact-id="{{ $stored->id }}">
                                        Unassign
                                    </button>
                                @endif
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No contacts found for this filter.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($lastPage > 1)
            <div class="flex items-center justify-between mt-4 text-sm text-gray-600">
                <span>
                    Showing {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }} of {{ $total }}
                </span>
                <div class="flex gap-1">
                    @if($currentPage > 1)
                        <a href="{{ $filterUrl($activeFilter, $currentPage - 1) }}"
                           class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50">← Prev</a>
                    @endif

                    @for($p = max(1, $currentPage - 2); $p <= min($lastPage, $currentPage + 2); $p++)
                        <a href="{{ $filterUrl($activeFilter, $p) }}"
                           class="px-3 py-1 rounded border transition-colors
                                  {{ $p === $currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' }}">
                            {{ $p }}
                        </a>
                    @endfor

                    @if($currentPage < $lastPage)
                        <a href="{{ $filterUrl($activeFilter, $currentPage + 1) }}"
                           class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50">Next →</a>
                    @endif
                </div>
            </div>
        @endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const TENANT_ID = '{{ $xeroTenant->id }}';
        const CSRF      = '{{ csrf_token() }}';

        // ── Click confidence badge → auto-select that customer in dropdown ────
        document.querySelectorAll('.use-suggestion-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const contactId  = this.dataset.contact;
                const customerId = this.dataset.customer;
                const select     = document.querySelector(`.customer-select[data-contact="${contactId}"]`);
                if (select) {
                    select.value = customerId;
                    select.classList.add('ring-2', 'ring-yellow-400');
                    setTimeout(() => select.classList.remove('ring-2', 'ring-yellow-400'), 1200);
                }
            });
        });

        // ── Select-all ────────────────────────────────────────────────────────
        document.getElementById('selectAll').addEventListener('change', function () {
            document.querySelectorAll('.contact-checkbox:not([disabled])')
                .forEach(cb => cb.checked = this.checked);
            updateBulkBtn();
        });

        document.querySelectorAll('.contact-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkBtn);
        });

        function updateBulkBtn() {
            const anyChecked = document.querySelectorAll('.contact-checkbox:checked').length > 0;
            document.getElementById('bulkMatchBtn').disabled = !anyChecked;
        }

        // ── Individual assign / reassign ──────────────────────────────────────
        document.querySelectorAll('.assign-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const contactId  = this.dataset.contact;
                const select     = document.querySelector(`.customer-select[data-contact="${contactId}"]`);
                const customerId = select?.value;

                if (!customerId) {
                    Swal.fire({ icon: 'warning', title: 'No customer selected', text: 'Please select a customer first.', confirmButtonColor: '#2563eb' });
                    return;
                }

                const original   = this.textContent;
                this.disabled    = true;
                this.textContent = 'Saving…';

                const res = await post('/admin/xero/assign-contact', {
                    contact_id:  contactId,
                    customer_id: customerId,
                    tenant_id:   TENANT_ID,
                });

                if (res.ok) {
                    await Swal.fire({ icon: 'success', title: 'Assigned!', text: res.message ?? 'Contact matched successfully.', timer: 1500, showConfirmButton: false });
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: res.message ?? 'Assignment failed.', confirmButtonColor: '#2563eb' });
                    this.disabled    = false;
                    this.textContent = original;
                }
            });
        });

        // ── Unassign ──────────────────────────────────────────────────────────
        document.querySelectorAll('.unassign-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const { isConfirmed } = await Swal.fire({
                    icon:               'warning',
                    title:              'Remove match?',
                    text:               'This will unlink the contact from its customer.',
                    showCancelButton:   true,
                    confirmButtonText:  'Yes, remove',
                    cancelButtonText:   'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor:  '#6b7280',
                });

                if (!isConfirmed) return;

                const xeroContactId = this.dataset.xeroContactId;
                const res  = await fetch(`/admin/xero/contacts/${xeroContactId}/match`, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                });
                const data = await res.json();

                if (data.ok) {
                    await Swal.fire({ icon: 'success', title: 'Removed', text: data.message ?? 'Match removed.', timer: 1500, showConfirmButton: false });
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message ?? 'Failed to remove match.', confirmButtonColor: '#2563eb' });
                }
            });
        });

        // ── Bulk assign ───────────────────────────────────────────────────────
        document.getElementById('bulkMatchBtn').addEventListener('click', async function () {
            const checked = [...document.querySelectorAll('.contact-checkbox:checked')];

            if (checked.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Nothing selected', text: 'Select at least one contact.', confirmButtonColor: '#2563eb' });
                return;
            }

            const assignments = [];
            const missing     = [];

            checked.forEach(cb => {
                const contactId  = cb.value;
                const select     = document.querySelector(`.customer-select[data-contact="${contactId}"]`);
                const customerId = select?.value;

                if (!customerId) {
                    missing.push(contactId);
                } else {
                    assignments.push({ contact_id: contactId, customer_id: parseInt(customerId, 10) });
                }
            });

            if (missing.length > 0) {
                Swal.fire({
                    icon:              'warning',
                    title:             'Missing selections',
                    text:              `${missing.length} selected row(s) have no customer chosen. Please select a customer for each before bulk-assigning.`,
                    confirmButtonColor: '#2563eb',
                });
                return;
            }

            const { isConfirmed } = await Swal.fire({
                icon:               'question',
                title:              'Bulk assign?',
                text:               `This will assign ${assignments.length} contact(s). Continue?`,
                showCancelButton:   true,
                confirmButtonText:  'Yes, assign all',
                cancelButtonText:   'Cancel',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor:  '#6b7280',
            });

            if (!isConfirmed) return;

            const original   = this.textContent;
            this.disabled    = true;
            this.textContent = 'Saving…';

            const res = await post('/admin/xero/bulk-assign', {
                assignments,
                tenant_id: TENANT_ID,
            });

            if (res.ok) {
                await Swal.fire({
                    icon:              'success',
                    title:             'Done!',
                    html:              `Assigned: <b>${res.assigned}</b> &nbsp; Skipped: <b>${res.skipped}</b> &nbsp; Failed: <b>${res.failed}</b>`,
                    confirmButtonColor: '#2563eb',
                });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: res.message ?? 'Bulk assign failed.', confirmButtonColor: '#2563eb' });
                this.disabled    = false;
                this.textContent = original;
            }
        });

        // ── POST helper ───────────────────────────────────────────────────────
        async function post(url, body) {
            const res = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    JSON.stringify(body),
            });
            return res.json();
        }
    </script>

</x-app-layout>

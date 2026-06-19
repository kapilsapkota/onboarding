<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Xero Contact Reconciliation
        </h2>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto sm:px-6 lg:px-8">
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

        {{-- SUMMARY PILLS --}}
        @php
            $totalCount   = count($contacts);
            $matchedCount = collect($contacts)->filter(fn($c) => $c['stored']?->isMatched())->count();
            $autoCount    = collect($contacts)->filter(fn($c) => ($c['match']['score'] ?? 0) >= 95 && !$c['stored']?->isMatched())->count();
            $pendingCount = $totalCount - $matchedCount - $autoCount;
        @endphp

        <div class="flex gap-3 mb-4 text-sm">
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium">
                {{ $matchedCount }} Matched
            </span>
            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full font-medium">
                {{ $autoCount }} Auto-Match Ready
            </span>
            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full font-medium">
                {{ $pendingCount }} Unmatched
            </span>
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

                @forelse($contacts as $contact)
                    @php
                        $stored      = $contact['stored'];           // XeroContact|null from DB
                        $matchResult = $contact['match'];            // ['client','score','method']|null from live matcher
                        $score       = $matchResult['score'] ?? 0;
                        $suggestion  = $matchResult['client'] ?? null;

                        $isConfirmed = $stored?->isMatched();        // human or previous sync approved
                        $isAuto      = !$isConfirmed && $score >= 95;
                        $isPossible  = !$isConfirmed && $score >= 60 && $score < 95;

                        // The client to pre-select: confirmed DB match takes priority, then suggestion
                        $preselectedId = $isConfirmed
                            ? $stored->client_id
                            : $suggestion?->id;

                        $rowBg = $isConfirmed ? 'bg-green-50 dark:bg-green-900/10'
                               : ($isAuto     ? 'bg-yellow-50 dark:bg-yellow-900/10'
                               :                '');
                    @endphp

                    <tr class="{{ $rowBg }} transition-colors" data-row="{{ $contact['ContactID'] }}">

                        {{-- CHECKBOX — disabled for already-confirmed rows --}}
                        <td class="p-3">
                            <input type="checkbox"
                                   class="contact-checkbox rounded"
                                   value="{{ $contact['ContactID'] }}"
                                {{ $isConfirmed ? 'disabled' : '' }}>
                        </td>

                        {{-- CONTACT NAME + method badge --}}
                        <td class="p-3 font-medium">
                            {{ $contact['Name'] ?? '-' }}

                            @if($isConfirmed)
                                <span class="ml-1 text-xs font-normal text-gray-400">
                                    via {{ $stored->match_method === 'manual' ? 'manual' : 'auto' }}
                                </span>
                            @endif
                        </td>

                        {{-- EMAIL --}}
                        <td class="p-3 text-gray-500">
                            {{ $contact['EmailAddress'] ?? '-' }}
                        </td>

                        {{-- PHONE --}}
                        <td class="p-3 text-gray-500">
                            {{ $contact['Phones'][0]['PhoneNumber'] ?? '-' }}
                        </td>

                        {{-- DROPDOWN — locked when confirmed --}}
                        <td class="p-3">
                            @if($isConfirmed)
                                {{-- Show the matched client name, no dropdown needed --}}
                                <a href="{{ route('clients.show', $stored->client->id) }}" class="font-medium text-blue-600 hover:underline">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $stored->client->company_name }}
                                </span>
                                </a>
                            @else
                                <select class="w-full border rounded p-1 text-sm customer-select
                                               focus:ring-2 focus:ring-blue-300 focus:outline-none"
                                        data-contact="{{ $contact['ContactID'] }}">

                                    <option value="">— Select customer —</option>

                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            {{ $preselectedId === $customer->id ? 'selected' : '' }}>
                                            {{ $customer->company_name }}
                                        </option>
                                    @endforeach

                                </select>
                            @endif
                        </td>

                        {{-- CONFIDENCE BADGE --}}
                        <td class="p-3">
                            @if($isConfirmed)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full font-medium">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                                    Confirmed
                                </span>
                            @elseif($isAuto)
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full font-medium">
                                    Auto ({{ round($score) }}%)
                                </span>
                            @elseif($isPossible)
                                <span class="px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded-full font-medium">
                                    Possible ({{ round($score) }}%)
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-500 rounded-full">
                                    No match
                                </span>
                            @endif
                        </td>

                        {{-- ACTIONS --}}
                        <td class="p-3">
                            @if($isConfirmed)
                                <button class="unassign-btn text-xs text-red-500 hover:text-red-700 underline"
                                        data-contact="{{ $contact['ContactID'] }}"
                                        data-xero-contact-id="{{ $stored->id }}">
                                    Unassign
                                </button>
                            @else
                                <button class="assign-btn px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-40"
                                        data-contact="{{ $contact['ContactID'] }}">
                                    Assign
                                </button>
                            @endif
                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No contacts found. Run a sync first.
                        </td>
                    </tr>
                @endforelse

                </tbody>
            </table>
        </div>

    </div>

    <script>
        const TENANT_ID = '{{ $xeroTenant->id }}';
        const CSRF     = '{{ csrf_token() }}';

        // ── Select-all (skips disabled/confirmed checkboxes) ─────────────────
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

        // ── Individual assign ─────────────────────────────────────────────────
        document.querySelectorAll('.assign-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const contactId  = this.dataset.contact;
                const select     = document.querySelector(`.customer-select[data-contact="${contactId}"]`);
                const customerId = select?.value;

                if (!customerId) {
                    alert('Please select a customer first.');
                    return;
                }

                this.disabled    = true;
                this.textContent = 'Saving…';

                const res  = await post('/admin/xero/assign-contact', {
                    contact_id:  contactId,
                    customer_id: customerId,
                    tenant_id:   TENANT_ID,
                });

                if (res.ok) {
                    // Reload so the row switches to confirmed state
                    location.reload();
                } else {
                    alert(res.message ?? 'Assignment failed.');
                    this.disabled    = false;
                    this.textContent = 'Assign';
                }
            });
        });

        // ── Unassign ──────────────────────────────────────────────────────────
        document.querySelectorAll('.unassign-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                if (!confirm('Remove this match?')) return;

                const xeroContactId = this.dataset.xeroContactId;

                const res = await fetch(`/admin/xero/contacts/${xeroContactId}/match`, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                });
                const data = await res.json();

                if (data.ok) {
                    location.reload();
                } else {
                    alert(data.message ?? 'Failed to remove match.');
                }
            });
        });

        // ── Bulk assign ───────────────────────────────────────────────────────
        // Collects { contact_id, customer_id } from every checked row's dropdown.
        // Warns the user if any checked row has no customer selected rather than
        // silently skipping it.
        document.getElementById('bulkMatchBtn').addEventListener('click', async function () {
            const checked = [...document.querySelectorAll('.contact-checkbox:checked')];

            if (checked.length === 0) {
                alert('Select at least one contact.');
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
                alert(`${missing.length} selected row(s) have no customer chosen. Please select a customer for each row before bulk-assigning.`);
                return;
            }

            this.disabled    = true;
            this.textContent = 'Saving…';

            const res = await post('/admin/xero/bulk-assign', {
                assignments,
                tenant_id: TENANT_ID,
            });

            if (res.ok) {
                alert(`Done — assigned: ${res.assigned}, skipped: ${res.skipped}, failed: ${res.failed}`);
                location.reload();
            } else {
                alert(res.message ?? 'Bulk assign failed.');
                this.disabled    = false;
                this.textContent = 'Bulk Match Selected';
            }
        });

        // ── Shared POST helper ────────────────────────────────────────────────
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

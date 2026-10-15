<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Reconcile All Customers') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Source of truth · every organisation"
                title="Reconcile all customers"
                subtitle="Left is the Xero customer (source of truth — never edited here). On the right, pick OUR customer record to link it to. Exact email and ABN matches can be linked automatically — the rest are confirmed here.">
                <x-slot name="actions">
                    @php
                        $scopeLabel = $tenant ? ' for ' . $tenant->tenant_name : (($company ? ' for ' . $company->name : '') . ($stripeAccount ? ($company ? ' · ' : ' for ') . $stripeAccount->display_name : '') ?: ' across all organisations');
                    @endphp
                    <form method="POST" action="{{ route('admin.xero.reconcile.auto-match') }}"
                          onsubmit="return confirm('Auto-link all exact email/ABN matches{{ $scopeLabel }}?')">
                        @csrf
                        @if($tenant)
                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                        @endif
                        @if($company)
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                        @endif
                        @if($stripeAccount)
                            <input type="hidden" name="stripe_account_id" value="{{ $stripeAccount->id }}">
                        @endif
                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Auto-match exact ({{ $stats['auto_ready'] }})
                        </button>
                    </form>
                    <button id="bulkMatchBtn" disabled
                            class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10 disabled:opacity-40">
                        Match selected
                    </button>
                </x-slot>
            </x-ui.hero>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <x-ui.stat label="Contacts" :value="number_format($stats['total'])" accent="slate" />
                <x-ui.stat label="Matched" :value="number_format($stats['matched'])" accent="emerald" />
                <x-ui.stat label="Unmatched" :value="number_format($stats['unmatched'])" accent="amber" />
                <x-ui.stat label="Auto-ready" :value="number_format($stats['auto_ready'])" accent="orange" />
            </div>

            <x-ui.card title="Needs review" :subtitle="$tenant ? $tenant->tenant_name : (($company ? $company->name . ($stripeAccount ? ' · ' . $stripeAccount->display_name : '') : ($stripeAccount ? $stripeAccount->display_name : 'All organisations')))">
                <x-slot name="actions">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="company" onchange="this.form.submit()"
                                class="max-w-[180px] rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All companies</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" {{ $company?->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <select name="stripe_account" onchange="this.form.submit()"
                                class="max-w-[200px] rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All Stripe accounts</option>
                            @foreach($stripeAccounts as $a)
                                <option value="{{ $a->id }}" {{ $stripeAccount?->id === $a->id ? 'selected' : '' }}>{{ $a->display_name }} ({{ $a->company?->name ?? 'No company' }})</option>
                            @endforeach
                        </select>
                        <select name="tenant" onchange="this.form.submit()"
                                class="max-w-[180px] rounded-xl border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="">All organisations</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ $tenant?->id === $t->id ? 'selected' : '' }}>{{ $t->tenant_name }}</option>
                            @endforeach
                        </select>
                        <input name="search" value="{{ request('search') }}" placeholder="Name or email…"
                               class="rounded-xl border-gray-200 bg-white px-3 py-2 text-xs shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-700">Filter</button>
                        @if(request()->hasAny(['company', 'stripe_account', 'tenant', 'search']))
                            <a href="{{ route('admin.xero.reconcile.index') }}" class="text-xs font-semibold text-gray-400 underline hover:text-gray-600">Clear</a>
                        @endif
                    </form>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
                        <tr>
                            <th class="w-10 px-6 py-3"><input type="checkbox" id="selectAll" class="h-4 w-4 rounded-lg border-gray-300 text-[#F06927] focus:ring-[#F06927]/40"></th>
                            <th class="px-4 py-3">Xero customer</th>
                            <th class="px-4 py-3">Organisation</th>
                            <th class="px-4 py-3">Suggestion</th>
                            <th class="px-4 py-3">
                                Match to our customer
                                @if($clientsScoped)
                                    <div class="mt-0.5 font-semibold normal-case tracking-normal text-[#F06927]">{{ $clients->count() }} for {{ $clientScopeNote }}</div>
                                @else
                                    <div class="mt-0.5 font-semibold normal-case tracking-normal text-gray-400">{{ $clientScopeNote ?: 'All customers — filter to narrow' }}</div>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($unmatched as $contact)
                            @php
                                $match = $suggestions[$contact->id] ?? ['customer' => null, 'score' => 0, 'method' => 'none'];
                                $suggestion = $match['customer'] ?? null;
                                $score = $match['score'] ?? 0;
                                $isAuto = $suggestion && $score >= 95 && in_array($match['method'], ['email', 'abn'], true);
                                $isPossible = $suggestion && $score >= 60 && !$isAuto;
                            @endphp
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ $isAuto ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}" data-row="{{ $contact->xero_contact_id }}">
                                <td class="px-6 py-4">
                                    <input type="checkbox" class="contact-checkbox h-4 w-4 rounded-lg border-gray-300 text-[#F06927] focus:ring-[#F06927]/40"
                                           value="{{ $contact->xero_contact_id }}" data-tenant="{{ $contact->xero_tenant_id }}">
                                </td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.xero.customers.show', $contact) }}" class="font-bold text-gray-900 hover:text-[#F06927] dark:text-gray-100">{{ $contact->name }}</a>
                                    <div class="text-xs text-gray-400">{{ $contact->email ?? 'No email' }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.xero.organisations.show', $contact->xero_tenant_id) }}" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300">
                                        {{ $contact->tenant?->tenant_name ?? '—' }}
                                    </a>
                                </td>
                                <td class="px-4 py-4">
                                    @if($isAuto && $suggestion)
                                        <button type="button" data-contact="{{ $contact->xero_contact_id }}" data-customer="{{ $suggestion->id }}" data-tenant="{{ $contact->xero_tenant_id }}"
                                                class="use-suggestion-btn inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700 transition hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300">
                                            ⚡ {{ $suggestion->company_name }} ({{ round($score) }}%)
                                        </button>
                                    @elseif($isPossible && $suggestion)
                                        <button type="button" data-contact="{{ $contact->xero_contact_id }}" data-customer="{{ $suggestion->id }}" data-tenant="{{ $contact->xero_tenant_id }}"
                                                class="use-suggestion-btn inline-flex items-center gap-1 rounded-full bg-orange-100 px-2.5 py-1 text-[11px] font-bold text-orange-700 transition hover:bg-orange-200 dark:bg-orange-900/40 dark:text-orange-300">
                                            ~ {{ $suggestion->company_name }} ({{ round($score) }}%)
                                        </button>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-400">No suggestion</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <select data-contact="{{ $contact->xero_contact_id }}" data-tenant="{{ $contact->xero_tenant_id }}"
                                            class="customer-select w-full min-w-[220px] rounded-xl border-gray-200 bg-white px-3 py-2 text-xs shadow-sm focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        <option value="">— Select our customer —</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ $suggestion?->id === $client->id ? 'selected' : '' }}>{{ $client->company_name }} — {{ $client->billing_email ?? 'no email' }}{{ $client->stripe_customer_id ? ' · ' . $client->stripe_customer_id : '' }}</option>
                                        @endforeach
                                    </select>
                                    @if($clients->isEmpty())
                                        <div class="mt-1 text-[11px] font-semibold text-gray-400">No customers linked to this scope yet.</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <button data-contact="{{ $contact->xero_contact_id }}" data-tenant="{{ $contact->xero_tenant_id }}"
                                            class="assign-btn inline-flex items-center rounded-xl bg-[#F06927] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow">
                                        Match
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Everything is reconciled. Nothing needs review.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>

            <div>{{ $unmatched->links() }}</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const CSRF = '{{ csrf_token() }}';

        function selectFor(contactId) {
            return document.querySelector(`.customer-select[data-contact="${contactId}"]`);
        }

        document.querySelectorAll('.use-suggestion-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const select = selectFor(this.dataset.contact);
                if (select) {
                    select.value = this.dataset.customer;
                    select.classList.add('ring-2', 'ring-amber-400');
                    setTimeout(() => select.classList.remove('ring-2', 'ring-amber-400'), 1200);
                }
            });
        });

        document.getElementById('selectAll').addEventListener('change', function () {
            document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkBtn();
        });

        document.querySelectorAll('.contact-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkBtn);
        });

        function updateBulkBtn() {
            document.getElementById('bulkMatchBtn').disabled = document.querySelectorAll('.contact-checkbox:checked').length === 0;
        }

        async function post(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body),
            });
            return res.json();
        }

        document.querySelectorAll('.assign-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const select = selectFor(this.dataset.contact);
                if (!select?.value) {
                    Swal.fire({ icon: 'warning', title: 'No customer selected', text: 'Pick our customer record on the right first.', confirmButtonColor: '#F06927' });
                    return;
                }
                const original = this.textContent;
                this.disabled = true;
                this.textContent = 'Saving…';
                const res = await post('/admin/xero/assign-contact', {
                    contact_id: this.dataset.contact,
                    customer_id: select.value,
                    tenant_id: this.dataset.tenant,
                });
                if (res.ok) {
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: res.message ?? 'Assignment failed.', confirmButtonColor: '#F06927' });
                    this.disabled = false;
                    this.textContent = original;
                }
            });
        });

        document.getElementById('bulkMatchBtn').addEventListener('click', async function () {
            const checked = [...document.querySelectorAll('.contact-checkbox:checked')];
            const rows = [];
            for (const cb of checked) {
                const select = selectFor(cb.value);
                if (!select?.value) {
                    Swal.fire({ icon: 'warning', title: 'Missing selections', text: 'Choose our customer for every selected row first.', confirmButtonColor: '#F06927' });
                    return;
                }
                rows.push({ contact_id: cb.value, customer_id: select.value, tenant_id: cb.dataset.tenant });
            }
            const { isConfirmed } = await Swal.fire({
                icon: 'question', title: 'Match all?', text: `This will match ${rows.length} contact(s). Continue?`,
                showCancelButton: true, confirmButtonText: 'Yes, match all', confirmButtonColor: '#F06927',
            });
            if (!isConfirmed) {
                return;
            }
            let done = 0, failed = 0;
            for (const row of rows) {
                try {
                    const res = await post('/admin/xero/assign-contact', row);
                    res.ok ? done++ : failed++;
                } catch (e) {
                    failed++;
                }
            }
            await Swal.fire({ icon: failed ? 'warning' : 'success', title: 'Done!', text: `Matched ${done}, failed ${failed}.`, confirmButtonColor: '#F06927' });
            location.reload();
        });
    </script>
</x-app-layout>


@php
    $isEdit = isset($invoice);
    $initialLines = old('line_items', $isEdit ? ($invoice->line_items ?? []) : [['description' => '', 'quantity' => 1, 'unit_amount' => '', 'account_code' => '', 'tax_type' => '', 'discount_rate' => '']]);
    $initialLines = collect($initialLines)->map(fn ($l) => [
        'line_item_id' => $l['line_item_id'] ?? null,
        'description' => $l['description'] ?? '',
        'quantity' => $l['quantity'] ?? 1,
        'unit_amount' => $l['unit_amount'] ?? '',
        'account_code' => $l['account_code'] ?? '',
        'tax_type' => $l['tax_type'] ?? '',
        'discount_rate' => $l['discount_rate'] ?? '',
    ])->values()->all();
    $linesJson = json_encode($initialLines, JSON_HEX_APOS | JSON_HEX_QUOT);
    $input = 'w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    $mini = 'w-full rounded-xl border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $isEdit ? 'Edit invoice ' . $invoice->display_number : 'New Xero invoice' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                :eyebrow="$isEdit ? 'Edit invoice' : 'New invoice'"
                :title="$isEdit ? 'Edit invoice ' . $invoice->display_number : 'New Xero invoice'"
                subtitle="Saved straight to Xero, then mirrored locally so the two never drift.">
                <x-slot name="actions">
                    <a href="{{ $isEdit ? route('admin.xero.invoices.show', $invoice) : route('admin.xero.invoices.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Cancel
                    </a>
                </x-slot>
            </x-ui.hero>

            <x-ui.card :title="$isEdit ? 'Invoice details' : 'New invoice details'" subtitle="Amounts are tax-exclusive.">
                <form method="POST"
                      action="{{ $isEdit ? route('admin.xero.invoices.update', $invoice) : route('admin.xero.invoices.store') }}"
                      class="space-y-6 px-6 py-6 sm:px-8">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field label="Organisation" required :error="$errors->first('xero_tenant_id')">
                            @if($isEdit)
                                <input type="text" disabled value="{{ $tenant->tenant_name }}" class="{{ $input }} bg-gray-50 dark:bg-gray-700/40">
                            @else
                                <select name="xero_tenant_id" required class="{{ $input }}">
                                    @foreach($tenants as $t)
                                        <option value="{{ $t->id }}" {{ (int) old('xero_tenant_id', $tenant->id) === $t->id ? 'selected' : '' }}>{{ $t->tenant_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </x-ui.field>
                        <x-ui.field label="Customer" required :error="$errors->first('xero_contact_id')">
                            <select name="xero_contact_id" required class="{{ $input }}">
                                @foreach($contacts as $c)
                                    <option value="{{ $c->id }}" {{ (int) old('xero_contact_id', $isEdit ? $invoice->xero_contact_id : request('contact')) === $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}@if($c->email) ({{ $c->email }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-4">
                        <x-ui.field label="Type" required>
                            <select name="type" class="{{ $input }}" {{ $isEdit ? 'disabled' : '' }}>
                                <option value="ACCREC" {{ old('type', $isEdit ? $invoice->type : 'ACCREC') === 'ACCREC' ? 'selected' : '' }}>Sales invoice</option>
                                <option value="ACCPAY" {{ old('type', $isEdit ? $invoice->type : '') === 'ACCPAY' ? 'selected' : '' }}>Bill</option>
                            </select>
                            @if($isEdit)<input type="hidden" name="type" value="{{ $invoice->type }}">@endif
                        </x-ui.field>
                        <x-ui.field label="Status" required hint="Authorised invoices lock against edits.">
                            <select name="status" class="{{ $input }}">
                                @foreach(['DRAFT','SUBMITTED','AUTHORISED'] as $s)
                                    <option value="{{ $s }}" {{ old('status', $isEdit ? $invoice->status : 'DRAFT') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field label="Invoice date" required>
                            <input type="date" name="invoice_date" required value="{{ old('invoice_date', $isEdit ? $invoice->invoice_date?->format('Y-m-d') : today()->format('Y-m-d')) }}" class="{{ $input }}">
                        </x-ui.field>
                        <x-ui.field label="Due date">
                            <input type="date" name="due_date" value="{{ old('due_date', $isEdit ? $invoice->due_date?->format('Y-m-d') : '') }}" class="{{ $input }}">
                        </x-ui.field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <x-ui.field label="Reference">
                            <input name="reference" maxlength="255" value="{{ old('reference', $isEdit ? $invoice->reference : '') }}" placeholder="PO / reference" class="{{ $input }}">
                        </x-ui.field>
                        <x-ui.field label="Currency">
                            <input name="currency_code" maxlength="3" value="{{ old('currency_code', $isEdit ? $invoice->currency_code : '') }}" placeholder="AUD" class="{{ $input }} uppercase">
                        </x-ui.field>
                        <x-ui.field label="Branding theme">
                            <select name="branding_theme_id" class="{{ $input }}">
                                <option value="">Default</option>
                                @foreach($themes as $theme)
                                    <option value="{{ $theme['BrandingThemeID'] ?? '' }}" {{ old('branding_theme_id', $isEdit ? $invoice->xero_branding_theme_id : '') === ($theme['BrandingThemeID'] ?? '') ? 'selected' : '' }}>
                                        {{ $theme['Name'] ?? $theme['BrandingThemeID'] }}
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>

                    {{-- LINE ITEMS --}}
                    <div x-data='{ "lines": {!! $linesJson !!} }'>
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Line items <span class="text-[#F06927]">*</span></span>
                            <button type="button" @click="lines.push({description:'',quantity:1,unit_amount:'',account_code:'',tax_type:'',discount_rate:''})"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow dark:bg-slate-700">+ Add line</button>
                        </div>
                        @error('line_items')<p class="mb-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        <div class="space-y-3">
                            <template x-for="(line, i) in lines" :key="i">
                                <div class="grid items-end gap-2 rounded-2xl bg-gray-50 p-4 md:grid-cols-12 dark:bg-gray-700/40">
                                    <input type="hidden" :name="`line_items[${i}][line_item_id]`" x-model="line.line_item_id">
                                    <div class="md:col-span-4">
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Description *</label>
                                        <input :name="`line_items[${i}][description]`" x-model="line.description" required class="{{ $mini }}">
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Qty *</label>
                                        <input type="number" step="any" min="0" :name="`line_items[${i}][quantity]`" x-model="line.quantity" required class="{{ $mini }}">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Unit *</label>
                                        <input type="number" step="0.01" min="0" :name="`line_items[${i}][unit_amount]`" x-model="line.unit_amount" required class="{{ $mini }}">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Account *</label>
                                        <select :name="`line_items[${i}][account_code]`" x-model="line.account_code" required class="{{ $mini }}">
                                            <option value="">Select…</option>
                                            @foreach($accounts as $a)
                                                <option value="{{ $a['Code'] ?? '' }}">{{ $a['Code'] ?? '' }} — {{ $a['Name'] ?? '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Tax</label>
                                        <select :name="`line_items[${i}][tax_type]`" x-model="line.tax_type" class="{{ $mini }}">
                                            <option value="">Default</option>
                                            @foreach($taxRates as $t)
                                                <option value="{{ $t['TaxType'] ?? '' }}">{{ $t['TaxType'] ?? '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-1">
                                        <button type="button" @click="lines.splice(i, 1)" x-show="lines.length > 1"
                                                class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline">Remove</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Removing a saved line on edit deletes it in Xero.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            {{ $isEdit ? 'Save to Xero' : 'Create in Xero' }}
                        </button>
                        <a href="{{ $isEdit ? route('admin.xero.invoices.show', $invoice) : route('admin.xero.invoices.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cancel</a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


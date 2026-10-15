@php
    $isEdit = isset($template);
    $initialLines = old('line_items', $isEdit ? ($template->line_items ?? []) : [['description' => '', 'quantity' => 1, 'unit_amount' => '', 'account_code' => '', 'tax_type' => '']]);
    $initialLines = collect($initialLines)->map(fn ($l) => [
        'description' => $l['description'] ?? '',
        'quantity' => $l['quantity'] ?? 1,
        'unit_amount' => $l['unit_amount'] ?? '',
        'account_code' => $l['account_code'] ?? '',
        'tax_type' => $l['tax_type'] ?? '',
    ])->values()->all();
    $linesJson = json_encode($initialLines, JSON_HEX_APOS | JSON_HEX_QUOT);
    $input = 'w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    $mini = 'w-full rounded-xl border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $isEdit ? 'Edit repeating invoice template' : 'New repeating invoice template' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                :eyebrow="$isEdit ? 'Edit template' : 'New template'"
                :title="$isEdit ? 'Edit repeating invoice template' : 'New repeating invoice template'"
                subtitle="Authorised templates start billing on schedule in Xero.">
                <x-slot name="actions">
                    <a href="{{ $isEdit ? route('admin.xero.repeating-invoices.show', $template) : route('admin.xero.repeating-invoices.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Cancel
                    </a>
                </x-slot>
            </x-ui.hero>

            <x-ui.card :title="$isEdit ? 'Template details' : 'New template details'">
                <form method="POST"
                      action="{{ $isEdit ? route('admin.xero.repeating-invoices.update', $template) : route('admin.xero.repeating-invoices.store') }}"
                      class="space-y-6 px-6 py-6 sm:px-8">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field label="Organisation" required>
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
                                    <option value="{{ $c->id }}" {{ (int) old('xero_contact_id', $isEdit ? $template->xero_contact_id : '') === $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}@if($c->email) ({{ $c->email }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field label="Type" required>
                            <select name="type" class="{{ $input }}" {{ $isEdit ? 'disabled' : '' }}>
                                <option value="ACCREC" {{ old('type', $isEdit ? $template->type : 'ACCREC') === 'ACCREC' ? 'selected' : '' }}>Sales invoice</option>
                                <option value="ACCPAY" {{ old('type', $isEdit ? $template->type : '') === 'ACCPAY' ? 'selected' : '' }}>Bill</option>
                            </select>
                            @if($isEdit)<input type="hidden" name="type" value="{{ $template->type }}">@endif
                        </x-ui.field>
                        <x-ui.field label="Status" required hint="Authorised templates start billing on schedule.">
                            <select name="status" class="{{ $input }}">
                                @foreach(['DRAFT','AUTHORISED'] as $s)
                                    <option value="{{ $s }}" {{ old('status', $isEdit ? $template->status : 'DRAFT') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-5 dark:bg-gray-700/40">
                        <div class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-gray-400">Schedule</div>
                        <div class="grid gap-5 md:grid-cols-3">
                            <x-ui.field label="Every" required>
                                <div class="flex gap-2">
                                    <input type="number" name="schedule_period" min="1" max="52" required
                                           value="{{ old('schedule_period', $isEdit ? $template->schedule_period : 1) }}"
                                           class="w-20 rounded-xl border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <select name="schedule_unit" class="flex-1 rounded-xl border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        @foreach(['WEEKLY','MONTHLY'] as $u)
                                            <option value="{{ $u }}" {{ old('schedule_unit', $isEdit ? $template->schedule_period_type : 'MONTHLY') === $u ? 'selected' : '' }}>{{ ucfirst(strtolower($u)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </x-ui.field>
                            <x-ui.field label="Due" required>
                                <div class="flex gap-2">
                                    <input type="number" name="schedule_due_date" min="0" required
                                           value="{{ old('schedule_due_date', $isEdit ? $template->schedule_due_date : 14) }}"
                                           class="w-20 rounded-xl border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <select name="schedule_due_date_type" class="flex-1 rounded-xl border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        @foreach(['DAYSAFTERBILLDATE' => 'Days after invoice date','DAYSAFTERBILLMONTH' => 'Days after month-end','OFTHECURRENTMONTH' => 'Of the current month','OFTHEFOLLOWINGMONTH' => 'Of the following month'] as $v => $label)
                                            <option value="{{ $v }}" {{ old('schedule_due_date_type', $isEdit ? $template->schedule_due_date_type : 'DAYSAFTERBILLDATE') === $v ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </x-ui.field>
                            <x-ui.field label="Reference">
                                <input name="reference" maxlength="255" value="{{ old('reference', $isEdit ? $template->reference : '') }}" class="{{ $input }}">
                            </x-ui.field>
                        </div>
                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <x-ui.field label="Start date" required>
                                <input type="date" name="schedule_start_date" required
                                       value="{{ old('schedule_start_date', $isEdit ? $template->schedule_start_date?->format('Y-m-d') : today()->format('Y-m-d')) }}"
                                       class="{{ $input }}">
                            </x-ui.field>
                            <x-ui.field label="End date (optional)">
                                <input type="date" name="schedule_end_date"
                                       value="{{ old('schedule_end_date', $isEdit ? $template->schedule_end_date?->format('Y-m-d') : '') }}"
                                       class="{{ $input }}">
                            </x-ui.field>
                        </div>
                    </div>

                    {{-- LINE ITEMS --}}
                    <div x-data='{ "lines": {!! $linesJson !!} }'>
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Line items <span class="text-[#F06927]">*</span></span>
                            <button type="button" @click="lines.push({description:'',quantity:1,unit_amount:'',account_code:'',tax_type:''})"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow dark:bg-slate-700">+ Add line</button>
                        </div>
                        @error('line_items')<p class="mb-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        <div class="space-y-3">
                            <template x-for="(line, i) in lines" :key="i">
                                <div class="grid items-end gap-2 rounded-2xl bg-gray-50 p-4 md:grid-cols-12 dark:bg-gray-700/40">
                                    <div class="md:col-span-4">
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Description *</label>
                                        <input :name="`line_items[${i}][description]`" x-model="line.description" required class="{{ $mini }}">
                                    </div>
                                    <div class="md:col-span-2">
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
                                    <div class="md:col-span-1">
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
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            {{ $isEdit ? 'Save to Xero' : 'Create in Xero' }}
                        </button>
                        <a href="{{ $isEdit ? route('admin.xero.repeating-invoices.show', $template) : route('admin.xero.repeating-invoices.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cancel</a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


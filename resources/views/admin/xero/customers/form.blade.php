@php $isEdit = isset($customer); @endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $isEdit ? 'Edit ' . $customer->name : 'New Xero customer' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                :eyebrow="$isEdit ? 'Edit customer' : 'New customer'"
                :title="$isEdit ? $customer->name : 'New Xero customer'"
                subtitle="Saved straight to Xero, then mirrored locally so the two never drift.">
                <x-slot name="actions">
                    <a href="{{ $isEdit ? route('admin.xero.customers.show', $customer) : route('admin.xero.customers.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Cancel
                    </a>
                </x-slot>
            </x-ui.hero>

            <x-ui.card :title="$isEdit ? 'Customer details' : 'New customer details'" subtitle="Writes through to Xero on save.">
                <form method="POST"
                      action="{{ $isEdit ? route('admin.xero.customers.update', $customer) : route('admin.xero.customers.store') }}"
                      class="space-y-6 px-6 py-6 sm:px-8">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    @if(!$isEdit)
                        <x-ui.field label="Organisation" required :error="$errors->first('xero_tenant_id')">
                            <select name="xero_tenant_id" required
                                    class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                @foreach($tenants as $t)
                                    <option value="{{ $t->id }}" {{ (int) old('xero_tenant_id', $tenant->id) === $t->id ? 'selected' : '' }}>{{ $t->tenant_name }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    @endif

                    <x-ui.field label="Name" required :error="$errors->first('name')">
                        <input name="name" required maxlength="255" value="{{ old('name', $isEdit ? $customer->name : '') }}" placeholder="Company or person name"
                               class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </x-ui.field>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field label="First name">
                            <input name="first_name" maxlength="255" value="{{ old('first_name', $isEdit ? $customer->first_name : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                        <x-ui.field label="Last name">
                            <input name="last_name" maxlength="255" value="{{ old('last_name', $isEdit ? $customer->last_name : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field label="Email" :error="$errors->first('email')">
                            <input type="email" name="email" maxlength="255" value="{{ old('email', $isEdit ? $customer->email : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                        <x-ui.field label="Phone">
                            <input name="phone" maxlength="50" value="{{ old('phone', $isEdit ? $customer->phone : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field label="Bank account details">
                            <input name="bank_account_details" maxlength="255" value="{{ old('bank_account_details', $isEdit ? $customer->bank_account_details : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                        <x-ui.field label="Tax number">
                            <input name="tax_number" maxlength="50" value="{{ old('tax_number', $isEdit ? $customer->tax_number : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <x-ui.field label="Account number">
                            <input name="account_number" maxlength="50" value="{{ old('account_number', $isEdit ? $customer->xero_account_number : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                        <x-ui.field label="Contact number">
                            <input name="contact_number" maxlength="50" value="{{ old('contact_number', $isEdit ? $customer->xero_contact_number : '') }}"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                        <x-ui.field label="Currency">
                            <input name="default_currency" maxlength="3" value="{{ old('default_currency', $isEdit ? $customer->default_currency : '') }}" placeholder="AUD"
                                   class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm uppercase text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </x-ui.field>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-100 transition hover:shadow dark:bg-gray-700/40 dark:text-gray-200 dark:ring-gray-600">
                            <input type="checkbox" name="is_customer" value="1" {{ old('is_customer', $isEdit ? $customer->is_customer : true) ? 'checked' : '' }}
                                   class="h-5 w-5 rounded-lg border-gray-300 text-[#F06927] focus:ring-[#F06927]/40">
                            Customer
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-100 transition hover:shadow dark:bg-gray-700/40 dark:text-gray-200 dark:ring-gray-600">
                            <input type="checkbox" name="is_supplier" value="1" {{ old('is_supplier', $isEdit ? $customer->is_supplier : false) ? 'checked' : '' }}
                                   class="h-5 w-5 rounded-lg border-gray-300 text-[#F06927] focus:ring-[#F06927]/40">
                            Supplier
                        </label>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            {{ $isEdit ? 'Save to Xero' : 'Create in Xero' }}
                        </button>
                        <a href="{{ $isEdit ? route('admin.xero.customers.show', $customer) : route('admin.xero.customers.index') }}"
                           class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cancel</a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


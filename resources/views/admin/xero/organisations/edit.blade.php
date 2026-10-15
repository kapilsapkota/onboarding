<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit links · {{ $organisation->tenant_name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert></x-alert>

            <x-ui.hero
                eyebrow="Source of truth"
                :title="$organisation->tenant_name"
                subtitle="Attach this Xero organisation to its company and default Stripe account. Tenants themselves sync from Xero — only local links are edited here.">
                <x-slot name="actions">
                    <a href="{{ route('admin.xero.organisations.show', $organisation) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                        Back to hub
                    </a>
                </x-slot>
            </x-ui.hero>

            <x-ui.card title="Organisation links" subtitle="These links drive collections, reconciliation and reporting.">
                <form method="POST" action="{{ route('admin.xero.organisations.update', $organisation) }}" class="space-y-6 px-6 py-6">
                    @csrf
                    @method('PUT')

                    <x-ui.field label="Company" :error="$errors->first('company_id')" hint="The business this organisation bills as. Clearing it also drops a company-owned default Stripe account.">
                        <select name="company_id" id="company_id"
                                class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">— No company —</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (string) old('company_id', $organisation->company_id) === (string) $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Default Stripe account" :error="$errors->first('default_stripe_account_id')" hint="Preferred account for collections against this organisation. Must belong to the linked company, or be company-less.">
                        <select name="default_stripe_account_id"
                                class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-[#F06927] focus:ring-2 focus:ring-[#F06927]/30 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">— No default account —</option>
                            @foreach($stripeAccounts as $account)
                                <option value="{{ $account->id }}" data-company="{{ $account->company_id ?? '' }}"
                                    {{ (string) old('default_stripe_account_id', $organisation->default_stripe_account_id) === (string) $account->id ? 'selected' : '' }}>
                                    {{ $account->display_name }} ({{ $account->company?->name ?? 'No company' }}{{ $account->is_legacy ? ' · Legacy' : '' }})
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-gray-100 dark:bg-gray-700/40 dark:ring-gray-600">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $organisation->is_active) ? 'checked' : '' }}
                               class="h-5 w-5 rounded-lg border-gray-300 text-[#F06927] focus:ring-[#F06927]/40">
                        <span>
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Organisation active</span>
                            <span class="block text-xs text-gray-400">Inactive organisations are hidden from pickers and sync runs.</span>
                        </span>
                    </label>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                            Save links
                        </button>
                        <a href="{{ route('admin.xero.organisations.show', $organisation) }}"
                           class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Cancel
                        </a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>


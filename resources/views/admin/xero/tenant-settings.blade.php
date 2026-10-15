<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            Xero Settings · {{ $tenant->tenant_name }}
        </h2>
    </x-slot>

    <div class="max-w-full mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-alert></x-alert>

        <x-ui.hero
            eyebrow="Direct debit"
            :title="$tenant->tenant_name"
            subtitle="Payments settled via Stripe BECS are posted against this account in Xero.">
            <x-slot name="actions">
                <a href="{{ route('admin.xero.organisations.show', $tenant) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10">
                    Back to hub
                </a>
            </x-slot>
        </x-ui.hero>

        <x-ui.card title="Clearing direct debit bank account" subtitle="Choose the Xero bank account that receives settled BECS payments.">
            <div class="px-6 py-6">
                @if($tenant->dd_bank_account_id)
                    <div class="mb-5 flex flex-wrap items-center gap-2 text-sm">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Configured
                        </span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $tenant->dd_bank_account_name }}</span>
                        <span class="font-mono text-xs text-gray-400">{{ $tenant->dd_bank_account_id }}</span>
                    </div>
                @else
                    <div class="mb-5">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            Not configured — payments to Xero will fail
                        </span>
                    </div>
                @endif

                @if($bankAccounts->isNotEmpty())
                    <form method="POST" action="{{ route('xero.tenants.update', $tenant) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach($bankAccounts as $account)
                                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition
                                    {{ $tenant->dd_bank_account_id === $account['account_id']
                                        ? 'border-[#F06927] bg-[#F06927]/5 shadow-md'
                                        : 'border-gray-100 hover:border-gray-300 hover:shadow dark:border-gray-700' }}">
                                    <input type="radio" name="dd_bank_account_id" value="{{ $account['account_id'] }}"
                                        {{ $tenant->dd_bank_account_id === $account['account_id'] ? 'checked' : '' }}
                                        class="h-5 w-5 border-gray-300 text-[#F06927] focus:ring-[#F06927]/40">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $account['name'] }}</p>
                                        <p class="font-mono text-xs text-gray-400">Code: {{ $account['code'] ?? '—' }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error('dd_bank_account_id')
                            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-5">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-[#F06927] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#D14F1A] hover:shadow-xl">
                                Save bank account
                            </button>
                        </div>
                    </form>
                @elseif(! $fetchError)
                    <p class="text-sm text-gray-500 dark:text-gray-400">No bank accounts found in Xero for this organisation.</p>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-app-layout>


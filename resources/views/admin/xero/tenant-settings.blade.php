<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            Xero Settings - {{ $tenant->tenant_name }}
        </h2>
    </x-slot>

    <div class="max-w-full mx-auto py-6 px-4 space-y-6">
        <x-alert></x-alert>

        <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">

                <div>
                    <h3 class="font-semibold text-lg">{{ $tenant->tenant_name }}</h3>
                    <p class="text-sm text-gray-500">
                        Payments settled via Stripe BECS will be posted against this account in Xero.
                    </p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.xero.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        Back
                    </a>
                </div>

            </div>
        </div>

        {{-- Current value --}}
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-base font-medium text-gray-900 mb-1">
                Set Up Clearing Direct Debit Bank Account
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                Payments settled via Stripe BECS will be posted against this account in Xero.
            </p>

            @if($tenant->dd_bank_account_id)
                <div class="mb-4 flex items-center gap-2 text-sm">
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-green-800 font-medium">
                        ✓ Configured
                    </span>
                    <span class="text-gray-700 font-medium">{{ $tenant->dd_bank_account_name }}</span>
                    <span class="text-gray-400 font-mono text-xs">{{ $tenant->dd_bank_account_id }}</span>
                </div>
            @else
                <div class="mb-4">
                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-yellow-800 text-sm font-medium">
                        ⚠ Not configured — payments to Xero will fail
                    </span>
                </div>
            @endif

            {{-- Picker form --}}
            @if($bankAccounts->isNotEmpty())
                <form method="POST" action="{{ route('xero.tenants.update', $tenant) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-3">
                        @foreach($bankAccounts as $account)
                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer
                                {{ $tenant->dd_bank_account_id === $account['account_id']
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200 hover:border-gray-300' }}">
                                <input
                                    type="radio"
                                    name="dd_bank_account_id"
                                    value="{{ $account['account_id'] }}"
                                    {{ $tenant->dd_bank_account_id === $account['account_id'] ? 'checked' : '' }}
                                    class="text-indigo-600 focus:ring-indigo-500"
                                >
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $account['name'] }}</p>
                                    <p class="text-xs text-gray-500">Code: {{ $account['code'] ?? '—' }}</p>
                                </div>
                            </label>
                        @endforeach

                        @error('dd_bank_account_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Save Bank Account
                        </button>
                    </div>
                </form>
            @elseif(! $fetchError)
                <p class="text-sm text-gray-500">No bank accounts found in Xero for this organisation.</p>
            @endif
        </div>

    </div>
</x-app-layout>

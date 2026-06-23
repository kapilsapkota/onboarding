<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Xero Integration
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <x-alert></x-alert>
            @if(!$connection)

                {{-- EMPTY STATE --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-10 text-center">

                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-white bg-sky-500">
                            X
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold mb-2">
                        Connect Xero to get started
                    </h3>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                        Sync invoices, contacts, payments, and repeating invoices across all organisations.
                    </p>

                    <a href="{{ route('admin.xero.connect') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Connect to Xero
                    </a>

                </div>

            @else

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                    {{-- HEADER --}}
                    <div class="flex justify-between items-start">

                        <div>
                            <div class="text-lg font-semibold">
                                Global Xero Connection
                            </div>

                            <div class="text-sm text-gray-500 mt-1">
                                {{ $connection->tenants->where('is_active', true)->count() }} active organisations
                            </div>

                            <div class="text-xs text-gray-400 mt-1">
                                Last updated: {{ optional($connection->updated_at)->diffForHumans() }}
                            </div>
                        </div>

                        <div class="flex gap-2">

                            @if($connection->is_active)
                                <form method="POST" action="{{ route('admin.xero.refresh', $connection) }}">
                                    @csrf
                                    <button class="px-3 py-1 text-sm border rounded hover:bg-gray-100">
                                        Refresh
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.xero.disconnect', $connection) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1 text-sm bg-red-600 text-white rounded">
                                        Disconnect
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('admin.xero.connect') }}"
                                   class="px-3 py-1 text-sm bg-blue-600 text-white rounded">
                                    Reconnect
                                </a>
                            @endif

                        </div>
                    </div>

                    {{-- TENANTS --}}
                    <div class="mt-6 space-y-4">

                        @foreach($connection->tenants as $tenant)

                            <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-900">

                                {{-- TOP ROW --}}
                                <div class="flex justify-between items-start">

                                    {{-- LEFT INFO --}}
                                    <div>

                                        <div class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $tenant->tenant_name }}
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            ID: {{ $tenant->tenant_id }}
                                        </div>

                                        <div class="mt-1 flex flex-wrap gap-2">

                                            @if($tenant->is_active)
                                                <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded">
                                                    Active
                                                </span>
                                            @else
                                                <span class="text-xs px-2 py-0.5 bg-red-100 text-red-700 rounded">
                                                    Inactive
                                                </span>
                                             @endif

                                            @if($tenant->tenant_type)
                                                <span class="text-xs px-2 py-0.5 bg-gray-200 text-gray-700 rounded">
                                                    {{ $tenant->tenant_type }}
                                                </span>
                                            @endif

                                        </div>

                                    </div>

                                    {{-- RIGHT: SYNC DROPDOWN --}}
                                    <form method="POST"
                                          action="{{ route('admin.xero.tenants.sync', $tenant) }}">
                                        @csrf

                                        <select name="type"
                                                onchange="this.form.submit()"
                                                class="text-xs border rounded px-2 py-1">

                                            <option selected disabled>Sync...</option>

                                            <option value="contacts">Contacts</option>
                                            <option value="invoices">Invoices</option>
                                            <option value="repeating">Repeating Invoices</option>

                                            <option value="all">Full Sync</option>
                                            <option value="full">Full Resync</option>

                                        </select>
                                    </form>

                                </div>

                                {{-- BANK ACCOUNT + QUICK STATUS --}}
                                <div class="mt-3 flex flex-wrap gap-2">

                                    @if($tenant->dd_bank_account_id)
                                        <span
                                            class="inline-flex items-center text-xs px-2 py-1 rounded bg-green-100 text-green-700">
                                            Direct Debit Account: {{ $tenant->dd_bank_account_name }}
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center text-xs px-2 py-1 rounded bg-amber-100 text-amber-700">
                                            No Direct Debit Bank Account
                                        </span>
                                    @endif

                                </div>

                                {{-- SYNC STATUS GRID --}}
                                <div class="mt-3 font-medium text-gray-800 dark:text-gray-200">
                                    SYNC STATUS :
                                </div>

                                <div class="mt-1 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                    <div>
                                        <div class="text-gray-500">Contacts</div>
                                        <div class="font-medium">
                                            {{ $tenant->last_contact_synced_at
                                                ? $tenant->last_contact_synced_at->diffForHumans()
                                                : 'Never' }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-gray-500">Invoices</div>
                                        <div class="font-medium">
                                            {{ $tenant->last_invoice_synced_at
                                                ? $tenant->last_invoice_synced_at->diffForHumans()
                                                : 'Never' }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-gray-500">Payments</div>
                                        <div class="font-medium">
                                            {{ $tenant->last_payment_synced_at
                                                ? $tenant->last_payment_synced_at->diffForHumans()
                                                : 'Never' }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-gray-500">Repeating</div>
                                        <div class="font-medium">
                                            {{ $tenant->last_repeating_invoice_synced_at
                                                ? $tenant->last_repeating_invoice_synced_at->diffForHumans()
                                                : 'Never' }}
                                        </div>
                                    </div>

                                </div>

                                {{-- ACTION BUTTONS --}}
                                <div class="mt-4 flex flex-wrap gap-2">

                                    {{-- VIEW CONTACTS --}}
                                    <a href="{{ route('admin.xero.contacts', [
                                                                            'xeroConnection' => $connection,
                                                                            'tenant' => $tenant,
                                                                            ]) }}"
                                       class="px-3 py-2 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                        Reconcile Contacts
                                    </a>
                                    {{-- BANK ACCOUNT SETUP --}}
                                    @if($tenant->dd_bank_account_id)
                                        <a href="{{ route('admin.xero.tenants.bank-settings', $tenant) }}"
                                           class="px-3 py-2 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                            Update DD Account
                                        </a>
                                    @else
                                        <a href="{{ route('admin.xero.tenants.bank-settings', $tenant) }}"
                                           class="px-3 py-2 text-xs bg-amber-600 text-white rounded hover:bg-amber-700">
                                            Setup DD Account
                                        </a>
                                    @endif

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endif

        </div>
    </div>
</x-app-layout>

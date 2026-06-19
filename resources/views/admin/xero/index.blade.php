<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Xero Integration
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mt-6">

                @if($connections->isEmpty())

                    {{-- EMPTY STATE --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-10 text-center">

                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-white bg-sky-500">
                                X
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mb-2">
                            Connect Xero to get started
                        </h3>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                            Sync customers, invoices, and payments automatically with Xero.
                        </p>

                        <a href="{{ route('admin.xero.connect') }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            Connect to Xero
                        </a>

                    </div>

                @else

                    <div class="space-y-4">

                        @foreach($connections as $conn)

                            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">

                                {{-- HEADER --}}
                                <div class="flex items-start justify-between">

                                    {{-- LEFT --}}
                                    <div class="flex items-start gap-4">

                                        <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-sky-500 text-white font-bold">
                                            X
                                        </div>

                                        <div>

                                            {{-- CONNECTION NAME --}}
                                            <div class="font-semibold">
                                                Connection #{{ $conn->id }}
                                            </div>

                                            @if($conn->xero_user_name || $conn->xero_user_email)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    Connected as
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                {{ $conn->xero_user_name ?? $conn->xero_user_email }}
            </span>
                                                    @if($conn->xero_user_name && $conn->xero_user_email)
                                                        <span class="text-gray-400">({{ $conn->xero_user_email }})</span>
                                                    @endif
                                                </div>
                                            @endif


                                            {{-- STATUS --}}
                                            <div class="flex items-center gap-2 mt-1">

                                                @if($conn->is_active)
                                                    <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-600">
                                                        Disconnected
                                                    </span>
                                                @endif

                                                @if($conn->isTokenExpired())
                                                    <span class="text-xs text-amber-600 font-medium">
                                                        Token expired
                                                    </span>
                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                    {{-- RIGHT ACTIONS --}}
                                    <div class="flex items-center gap-2">

                                        @if($conn->is_active)

                                            <form method="POST" action="{{ route('admin.xero.refresh', $conn) }}">
                                                @csrf
                                                <button class="px-3 py-1 text-sm border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Refresh
                                                </button>
                                            </form>

                                            <form method="POST"
                                                  action="{{ route('admin.xero.disconnect', $conn) }}"
                                                  onsubmit="return confirm('Disconnect this Xero connection?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                                    Disconnect
                                                </button>
                                            </form>

                                        @else

                                            <a href="{{ route('admin.xero.connect') }}"
                                               class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Reconnect
                                            </a>

                                        @endif

                                    </div>

                                </div>

                                {{-- TENANTS (SCALABLE BLOCK) --}}
                                {{-- TENANTS (SCALABLE BLOCK) --}}
                                @if($conn->relationLoaded('tenants') && $conn->tenants->count())

                                    <div class="mt-4 border-t pt-4">

                                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            Organisations
                                        </div>

                                        <div class="space-y-2">

                                            @foreach($conn->tenants as $tenant)

                                                <div class="flex items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2">

                                                    {{-- Tenant name + DD badge --}}
                                                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $tenant->tenant_name }}
                        </span>

                                                        @if($tenant->dd_bank_account_id)
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs text-green-700 dark:text-green-300">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                {{ $tenant->dd_bank_account_name }}
                            </span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900 px-2 py-0.5 text-xs text-amber-700 dark:text-amber-300">
                                No DD account
                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- Actions --}}
                                                    <div class="flex items-center gap-2">

                                                        <a href="{{ route('admin.xero.contacts', [
                                'xeroConnection' => $conn,
                                'tenant'         => $tenant,
                            ]) }}"
                                                           class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                            Contacts
                                                        </a>

                                                        <a href="{{ route('xero.tenants.edit', $tenant) }}"
                                                           class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            Settings
                                                        </a>

                                                    </div>

                                                </div>

                                            @endforeach

                                        </div>

                                    </div>

                                @endif
                                {{-- TOKEN BAR --}}
                                @if($conn->is_active && ! $conn->isTokenExpired())

                                    @php
                                        $totalSeconds = 1800;
                                        $remaining = now()->diffInSeconds($conn->token_expires_at, false);
                                        $pct = max(0, min(100, ($remaining / $totalSeconds) * 100));
                                    @endphp

                                    <div class="mt-4">
                                        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-green-500" style="width: {{ $pct }}%"></div>
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ gmdate('i:s', max(0, $remaining)) }} remaining
                                        </div>
                                    </div>

                                @endif

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>

        </div>
    </div>
</x-app-layout>

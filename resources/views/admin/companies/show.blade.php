<x-app-layout>
    @section('title', $company->name)

    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.companies.index') }}"
                   class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $company->name }}
                </h2>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('admin.companies.edit', $company) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <x-message></x-message>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header card: logo + name --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 flex items-center gap-6">
                @if($company->logo)
                    <img src="{{ asset('images/' . $company->logo) }}"
                         alt="{{ $company->name }}"
                         class="h-20 w-auto max-w-[140px] object-contain rounded">
                @else
                    <div class="h-20 w-20 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                @endif
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $company->name }}</h3>
                    @if($company->slug)
                        <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ $company->slug }}
                        </span>
                    @endif
                    <p class="mt-1 text-xs text-gray-400">Added {{ $company->created_at->format('M d, Y') }}</p>
                </div>
            </div>

            {{-- Contact Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                    Contact Details
                </h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Email</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            @if($company->email)
                                <a href="mailto:{{ $company->email }}" class="text-yellow-600 dark:text-yellow-400 hover:underline">
                                    {{ $company->email }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Phone</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            @if($company->phone)
                                <a href="tel:{{ $company->phone }}" class="text-yellow-600 dark:text-yellow-400 hover:underline">
                                    {{ $company->phone }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>

                </dl>
            </div>

            {{-- Address --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                    Address
                </h3>
                @php
                    $hasAddress = $company->address || $company->city || $company->state || $company->postal_code || $company->country;
                @endphp
                @if($hasAddress)
                    <address class="not-italic text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                        @if($company->address) <span class="block">{{ $company->address }}</span> @endif
                        @php
                            $cityLine = array_filter([$company->city, $company->state, $company->postal_code]);
                        @endphp
                        @if($cityLine) <span class="block">{{ implode(', ', $cityLine) }}</span> @endif
                        @if($company->country) <span class="block">{{ $company->country }}</span> @endif
                    </address>
                @else
                    <p class="text-sm text-gray-400">No address on file.</p>
                @endif
            </div>

            {{-- Danger Zone --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-red-200 dark:border-red-900/40">
                <h3 class="text-sm font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide mb-3">
                    Danger Zone
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Permanently delete this company. This action cannot be undone.
                </p>
                <x-delete-modal-button
                    :model="$company"
                    :action="route('admin.companies.destroy', $company)"
                    title="Delete Company"
                    :display_name="$company->name"
                >Delete Company</x-delete-modal-button>
            </div>

        </div>
    </div>

</x-app-layout>

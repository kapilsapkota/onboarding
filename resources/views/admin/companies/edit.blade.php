<x-app-layout>
    @section('title', 'Edit ' . $company->name)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.companies.index') }}"
               class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit - {{ $company->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('admin.companies.update', $company) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('admin.companies.partials.form', ['company' => $company])

                {{-- Actions --}}
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('admin.companies.show', $company) }}"
                       class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 underline transition">
                        ← Back to {{ $company->name }}
                    </a>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.companies.index') }}"
                           class="inline-flex px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                            Save Changes
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</x-app-layout>

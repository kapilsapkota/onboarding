<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="AIIT Onboarding">
    <meta name="author" content="">
    <link rel="icon" href="{{ asset('images/aiit.webp') }}">

    <title>{{ config('app.name', 'Onboarding AIIT') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>
</head>

<body
    class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900 min-h-screen flex"
    x-data="{
        collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        mobileOpen: false,
        init() {
            this.$watch('collapsed', val => localStorage.setItem('sidebarCollapsed', val));
        }
    }"
>

{{-- Sidebar --}}
<div class="print:hidden">
    @include('layouts.navigation')
</div>

{{-- Main content shifts via margin-left, matching sidebar width --}}
<div
    class="flex flex-col flex-1 min-h-screen min-w-0 overflow-hidden transition-all duration-200 ease-in-out"
    :class="{
            'lg:ml-64': !collapsed,
            'lg:ml-16': collapsed
        }"
>
    {{-- Top Bar --}}
    <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-sm sticky top-0 z-30 w-full print:hidden">
        <div class="px-4 py-4 sm:px-6 lg:px-8 flex items-center gap-4">

            {{-- Mobile hamburger --}}
            <button @click="mobileOpen = true"
                    type="button"
                    class="p-2 -ml-2 text-gray-500 rounded-md lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 shrink-0">
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            @isset($header)
                <div class="w-full">{{ $header }}</div>
            @endisset
        </div>
    </header>

    <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 w-full print:w-full">
        {{ $slot }}
    </main>
</div>

<script>
    function openDeleteModal(id) {
        const modal = document.getElementById(`deleteModal_${id}`);
        if (modal) modal.classList.remove('hidden');
    }
    function closeDeleteModal(id) {
        const modal = document.getElementById(`deleteModal_${id}`);
        if (modal) modal.classList.add('hidden');
    }
</script>
@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="AIIT Onboarding">
    <meta name="author" content="">
    <link rel="icon" href="{{ asset('images/aiit.webp') }}">

    <title>{{ config('app.name', 'AIIT') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://bunny.net">
    <link href="https://bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Inline fix for Alpine x-cloak utility -->
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900" x-data="{ sidebarOpen: false }">

<!-- Sidebar Layout Included Nationally -->
@include('layouts.navigation')

<!-- Main Content wrapper containing clean desktop margin adjustments -->
<div class="flex flex-col min-h-screen md:pl-64">

    <!-- Unified App Top Bar Header Layout -->
    <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-sm sticky top-0 z-30 w-full">
        <div class="px-4 py-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-4 w-full">

                <!-- Mobile Hamburger Button - ALWAYS visible on mobile, outside isset rule -->
                <button @click="sidebarOpen = true"
                        type="button"
                        class="p-2 -ml-2 text-gray-500 rounded-md md:hidden hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 shrink-0">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Render Header Title Text cleanly when defined -->
                @isset($header)
                    <div class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ $header }}
                    </div>
                @endisset
            </div>
        </div>
    </header>

    <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 w-full max-w-full mx-auto">
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
</body>
</html>

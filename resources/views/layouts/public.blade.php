<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased bg-gray-100 dark:bg-gray-900">

<div class="min-h-screen flex flex-col">

    {{-- Header --}}
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

            <div class="flex items-center gap-3">
                <x-application-logo class="w-10 h-10 fill-current text-gray-600"/>
                <span class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                    {{ config('app.name') }}
                </span>
            </div>

            {{-- Optional right slot --}}
            @isset($headerRight)
                <div>{{ $headerRight }}</div>
            @endisset

        </div>
    </header>

    {{-- Page header slot (matches layouts.app pattern) --}}
    @isset($header)
        <div class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-6 py-6">
                {{ $header }}
            </div>
        </div>
    @endisset

    {{-- Main --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="py-6 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        This quotation is confidential.
    </footer>

</div>

</body>
</html>

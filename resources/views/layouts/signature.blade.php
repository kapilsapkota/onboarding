<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased bg-gray-100 dark:bg-gray-900">

<div class="min-h-screen flex flex-col">

    {{-- Navigation --}}
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

            <a href="/" class="flex items-center gap-3">
                <x-application-logo class="w-10 h-10 fill-current text-gray-600" />

                <span class="font-semibold text-lg">
                    {{ config('app.name') }}
                </span>
            </a>

            <nav class="flex items-center gap-4">
                @guest
                    <a href="{{ route('login') }}"
                       class="px-4 py-2 rounded-lg border hover:bg-gray-100">
                        Login
                    </a>

                    @if(Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Register
                        </a>
                    @endif
                @endguest
            </nav>

        </div>
    </header>


    {{-- Page Header Slot --}}
    @isset($header)
        <div class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-6 py-6">
                {{ $header }}
            </div>
        </div>
    @endisset


    {{-- Main Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>


    {{-- Footer --}}
    <footer class="py-6 text-center text-sm text-gray-500">
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </footer>

</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All done! — {{ config('app.name', 'Wild Marketing') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --wld-gold: #C9A84C; --wld-gold-light: #FBF6E9; }
        body { font-family: 'Inter', sans-serif; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp 0.5s cubic-bezier(0.4,0,0.2,1) both; }
    </style>
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center px-4">

<div class="text-center max-w-md mx-auto fade-up">

    {{-- Check icon --}}
    <div class="w-16 h-16 rounded-full mx-auto mb-6 flex items-center justify-center"
         style="background:var(--wld-gold-light)">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             style="color:var(--wld-gold)">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    {{-- Logo --}}
{{--    <div class="flex items-center justify-center gap-2 mb-6">--}}
{{--        <img src="{{ asset('logo.webp') }}" alt="Wild Marketing" class="h-6 w-auto opacity-80 dark:invert">--}}
{{--    </div>--}}

    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100 mb-3">
        You're all set.
    </h1>

    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
        Your profile has been received. We'll reach out if we need anything else to complete your file — otherwise, we've got everything we need to get started.
    </p>

    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-400 dark:text-gray-500">You can safely close this window.</p>
    </div>

</div>

</body>
</html>

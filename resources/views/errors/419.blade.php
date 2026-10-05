<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>419 | Page Expired - GeoLogistics</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-100 text-gray-900">

    <div class="min-h-screen flex flex-col items-center justify-center text-center px-4">

        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 mb-5">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-3xl font-bold text-slate-900">
            419 | Page Expired
        </h1>

        <p class="mt-2 text-sm text-slate-600 max-w-md">
            Your session has expired. Please refresh the page and try again.
        </p>

        <div class="mt-6">
            <a href="/dashboard"
               class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">
                Back to Dashboard
            </a>
        </div>

    </div>

    @livewireScripts

</body>

</html>

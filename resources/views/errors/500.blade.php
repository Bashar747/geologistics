<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>500 | Server Error - GeoLogistics</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-100 text-gray-900">

    <div class="min-h-screen flex flex-col items-center justify-center text-center px-4">

        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-red-100 text-red-600 mb-5">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v3m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.42 0z"/>
            </svg>
        </div>

        <h1 class="text-3xl font-bold text-slate-900">
            500 | Server Error
        </h1>

        <p class="mt-2 text-sm text-slate-600 max-w-md">
            Something went wrong on our server. Please try again later.
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

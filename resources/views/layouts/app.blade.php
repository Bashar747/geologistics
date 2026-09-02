<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        {{ $title ?? 'GeoLogistics' }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-100 text-gray-900">

    @auth

        <div class="min-h-screen flex">

            {{-- Sidebar --}}
            <aside class="w-64 bg-gray-900 text-white min-h-screen">

                <div class="p-6">
                    <h1 class="text-xl font-bold">
                        GeoLogistics
                    </h1>
                </div>

                <nav class="px-4 space-y-2">

                    <a
                        href="/dashboard"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Dashboard
                    </a>

                    <a
                        href="/shipments"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Shipments
                    </a>

                    <a
                        href="/drivers"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Drivers
                    </a>

                    <a
                        href="/vehicles"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Vehicles
                    </a>

                    <a
                        href="/tracking"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Tracking
                    </a>

                    <a
                        href="/payments"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Payments
                    </a>

                    <a
                        href="/users"
                        wire:navigate
                        class="block px-4 py-2 rounded hover:bg-gray-800"
                    >
                        Users
                    </a>

                </nav>

            </aside>


            {{-- Main --}}
            <div class="flex-1">

                {{-- Navbar --}}
                <header class="bg-white border-b px-6 py-4">

                    <div class="flex items-center justify-between">

                        <h2 class="text-lg font-semibold">
                            {{ $title ?? 'Dashboard' }}
                        </h2>


                        {{-- User --}}
                        <div class="flex items-center gap-4">

                            <div class="text-right">

                                <div class="text-sm font-semibold text-slate-900">
                                    {{ auth()->user()->name }}
                                </div>

                                <div class="text-xs text-slate-500 capitalize">
                                    {{ auth()->user()->role }}
                                </div>

                            </div>


                            {{-- Logout --}}
                            <form method="POST" action="/logout">

                                @csrf

                                <button
                                    type="submit"
                                    class="px-3 py-2 text-sm font-medium
                                           text-slate-600 hover:text-red-600
                                           transition"
                                >
                                    Logout
                                </button>

                            </form>

                        </div>

                    </div>

                </header>


                {{-- Page --}}
                <main class="p-6">
                    {{ $slot }}
                </main>

            </div>

        </div>

    @else

        {{-- Guest pages: Login / Register --}}
        {{ $slot }}

    @endauth


    @livewireScripts

</body>

</html>
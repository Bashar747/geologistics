<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        {{ $title ?? 'GeoLogistics' }}
    </title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles


</head>

<body class="bg-gray-100 text-gray-900">

    @auth
               <script>
                window.GeoLogisticsUserId = @json(auth()->id());
               </script>
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

                    @if (in_array(auth()->user()->role, ['admin', 'dispatcher']))
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
                    @endif

                    @if (in_array(auth()->user()->role, ['admin', 'dispatcher', 'driver']))
                        <a
                            href="/tracking"
                            wire:navigate
                            class="block px-4 py-2 rounded hover:bg-gray-800"
                        >
                            Tracking
                        </a>
                    @endif

                    @if (in_array(auth()->user()->role, ['admin', 'dispatcher', 'customer']))
                        <a
                            href="/payments"
                            wire:navigate
                            class="block px-4 py-2 rounded hover:bg-gray-800"
                        >
                            Payments
                        </a>
                    @endif

                    @if (in_array(auth()->user()->role, ['admin', 'dispatcher']))
                        <a
                            href="/users"
                            wire:navigate
                            class="block px-4 py-2 rounded hover:bg-gray-800"
                        >
                            Users
                        </a>

                        <a
                            href="/geofences"
                            wire:navigate
                            class="block px-4 py-2 rounded hover:bg-gray-800"
                        >
                            Geofences
                        </a>
                    @endif

                   <a
    href="/notifications"
    wire:navigate
    id="notifications-link"
    class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-800"
>
    <span class="flex items-center gap-2">
        <span>🔔</span>
        <span>Notifications</span>
    </span>

    <span
        id="notification-badge"
        class="hidden min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-xs font-bold items-center justify-center"
    >
        0
    </span>
</a>

<div
    id="notification-toast"
    class="hidden fixed top-5 right-5 z-[9999] w-80 rounded-lg bg-white text-gray-900 shadow-lg border border-gray-200 p-4"
>
    <div class="flex items-start gap-3">
        <div class="text-lg">🔔</div>

        <div class="min-w-0">
            <div class="font-semibold text-sm">
                New notification
            </div>

            <div
                id="notification-toast-message"
                class="mt-1 text-sm text-gray-600"
            ></div>
        </div>
    </div>
</div>
                    @if (auth()->user()->role === 'admin')

                        <a
                            href="/audit-logs"
                            wire:navigate
                            class="block px-4 py-2 rounded hover:bg-gray-800"
                        >
                            Audit Logs
                        </a>

                    @endif
                         <a
    href="/profile"
    wire:navigate
    class="block px-4 py-2 rounded hover:bg-gray-800"
>
    Profile
</a>

            
    @if(auth()->user()->role === 'admin')
        <a
            href="{{ route('settings.index') }}"
            wire:navigate
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-800"
        >
            <span>⚙️</span>
            <span>Settings</span>
        </a>
    @endif

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GeoLogistics — Smart Logistics & Fleet Management</title>

    <meta
        name="description"
        content="GeoLogistics is a real-time logistics and fleet management platform for shipments, vehicles, drivers, tracking and geofencing."
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#05070d] text-white antialiased">
    <div
    aria-hidden="true"
    class="pointer-events-none fixed inset-0 -z-10"
    style="background:
        radial-gradient(circle at 50% 0%, rgba(37,99,235,0.14), transparent 35%),
        radial-gradient(circle at 100% 50%, rgba(124,58,237,0.08), transparent 30%),
        #05070d;"
></div>

    {{-- Navigation --}}
    <header class="absolute inset-x-0 top-0 z-50">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">

            <a href="/" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-600/20">
                    <svg
                        class="h-6 w-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M3 7h11v10H3z" />
                        <path d="M14 10h3l4 4v3h-7z" />
                        <circle cx="7" cy="18" r="2" />
                        <circle cx="18" cy="18" r="2" />
                    </svg>
                </div>

                <div>
                    <div class="text-lg font-bold tracking-tight">
                        GeoLogistics
                    </div>

                    <div class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-500">
                        Logistics Platform
                    </div>
                </div>
            </a>

            <nav class="flex items-center gap-3">

                @auth
                    <a
                        href="/dashboard"
                        wire:navigate
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                    >
                        Dashboard
                    </a>
                @else
                    <a
                        href="/login"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                    >
                        Sign in
                    </a>

                    <a
                        href="/register"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200"
                    >
                        Get started
                    </a>
                @endauth

            </nav>
        </div>
    </header>


    {{-- Hero --}}
    <main class="relative isolate overflow-hidden">

    {{-- Premium dark background --}}
<div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
    {{-- Blue ambient glow --}}
    <div class="absolute left-1/2 top-[-220px] h-[520px] w-[900px] -translate-x-1/2 rounded-full bg-blue-600/8 blur-[150px]"></div>

    {{-- Purple ambient glow --}}
    <div class="absolute right-[-220px] top-[300px] h-[450px] w-[450px] rounded-full bg-violet-600/6 blur-[140px]"></div>

    {{-- Technical grid --}}
    <div
        class="absolute inset-0 opacity-[0.018]"
        style="
            background-image:
                linear-gradient(rgba(255,255,255,.7) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.7) 1px, transparent 1px);
            background-size: 56px 56px;
        "
    ></div>

    {{-- Top light line --}}
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/25 to-transparent"></div>
</div>

   

    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/40 to-transparent"></div>
</div>

        <div class="mx-auto max-w-7xl px-6 pb-24 pt-36 lg:px-8 lg:pt-44">

            <div class="grid items-center gap-16 lg:grid-cols-2">

                {{-- Hero copy --}}
                <div>

                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-400/20 bg-blue-400/10 px-3 py-1.5 text-sm font-medium text-blue-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Real-time logistics operations
                    </div>

                    <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-white sm:text-6xl lg:text-7xl">
                        Move smarter.
                        <span class="text-blue-500">
                            Track everything.
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-400">
                        GeoLogistics brings shipments, vehicles, drivers and live fleet
                        operations into one powerful logistics management platform.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">

                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500"
                        >
                            Open dashboard

                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </a>

                        <a
                            href="/tracking"
                            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Live tracking
                        </a>

                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-500">
                        <span class="flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            Real-time fleet visibility
                        </span>

                        <span class="flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            Secure role-based access
                        </span>

                        <span class="flex items-center gap-2">
                            <span class="text-emerald-400">✓</span>
                            PostGIS powered
                        </span>
                    </div>

                </div>


                {{-- Operations dashboard preview --}}
                <div class="relative">

                    <div class="absolute -inset-6 rounded-[2rem] bg-blue-600/10 blur-3xl"></div>

                    <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/40">

                        {{-- Browser header --}}
                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">

                            <div class="flex gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-700"></span>
                            </div>

                            <div class="text-xs text-slate-500">
                                Fleet Operations
                            </div>

                            <div class="h-2 w-2 rounded-full bg-emerald-400"></div>

                        </div>


                        {{-- Dashboard preview --}}
                        <div class="p-5">

                            <div class="grid grid-cols-3 gap-3">

                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-4">
                                    <div class="text-xs text-slate-500">
                                        Active vehicles
                                    </div>

                                    <div class="mt-2 text-2xl font-bold">
                                        24
                                    </div>

                                    <div class="mt-1 text-xs text-emerald-400">
                                        Live
                                    </div>
                                </div>

                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-4">
                                    <div class="text-xs text-slate-500">
                                        Shipments
                                    </div>

                                    <div class="mt-2 text-2xl font-bold">
                                        148
                                    </div>

                                    <div class="mt-1 text-xs text-blue-400">
                                        In transit
                                    </div>
                                </div>

                                <div class="rounded-xl border border-white/5 bg-white/[0.03] p-4">
                                    <div class="text-xs text-slate-500">
                                        On time
                                    </div>

                                    <div class="mt-2 text-2xl font-bold">
                                        96%
                                    </div>

                                    <div class="mt-1 text-xs text-emerald-400">
                                        +4.2%
                                    </div>
                                </div>

                            </div>


                            {{-- Map --}}
                            <div class="relative mt-4 h-64 overflow-hidden rounded-xl border border-white/5 bg-slate-950">

                                <div class="absolute inset-0 opacity-30"
                                     style="
                                        background-image:
                                            linear-gradient(rgba(59,130,246,.12) 1px, transparent 1px),
                                            linear-gradient(90deg, rgba(59,130,246,.12) 1px, transparent 1px);
                                        background-size: 32px 32px;
                                     ">
                                </div>

                                {{-- Route --}}
                                <svg
                                    class="absolute inset-0 h-full w-full"
                                    viewBox="0 0 600 260"
                                    preserveAspectRatio="none"
                                >
                                    <path
                                        d="M70 190 C150 170 160 80 270 105 S390 190 520 70"
                                        fill="none"
                                        stroke="rgba(59,130,246,.55)"
                                        stroke-width="3"
                                        stroke-dasharray="8 8"
                                    />
                                </svg>

                                {{-- Vehicle markers --}}
                                <div class="absolute left-[15%] top-[68%] flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 shadow-lg shadow-blue-600/40">
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 7h11v10H3z" />
                                        <path d="M14 10h3l4 4v3h-7z" />
                                        <circle cx="7" cy="18" r="2" />
                                        <circle cx="18" cy="18" r="2" />
                                    </svg>
                                </div>

                                <div class="absolute left-[43%] top-[38%] flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/30">
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 7h11v10H3z" />
                                        <path d="M14 10h3l4 4v3h-7z" />
                                        <circle cx="7" cy="18" r="2" />
                                        <circle cx="18" cy="18" r="2" />
                                    </svg>
                                </div>

                                <div class="absolute left-[82%] top-[22%] flex h-8 w-8 items-center justify-center rounded-full bg-violet-500 shadow-lg shadow-violet-500/30">
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 7h11v10H3z" />
                                        <path d="M14 10h3l4 4v3h-7z" />
                                        <circle cx="7" cy="18" r="2" />
                                        <circle cx="18" cy="18" r="2" />
                                    </svg>
                                </div>

                                <div class="absolute bottom-4 left-4 rounded-lg border border-white/10 bg-slate-900/90 px-3 py-2 text-xs backdrop-blur">
                                    <div class="font-semibold text-white">
                                        Fleet live
                                    </div>

                                    <div class="mt-0.5 text-slate-500">
                                        24 vehicles connected
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>


        {{-- Features --}}
        <section class="border-y border-white/5 bg-white/[0.02]">

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="max-w-2xl">

                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-400">
                        One platform
                    </p>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        Everything your logistics operation needs.
                    </h2>

                    <p class="mt-4 text-slate-400">
                        Manage the complete delivery lifecycle from one centralized
                        operational platform.
                    </p>

                </div>


                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- Feature --}}
                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 transition hover:-translate-y-1 hover:border-blue-500/30">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                            📦
                        </div>

                        <h3 class="mt-5 text-lg font-semibold">
                            Shipment Management
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Manage shipments, statuses, pricing, ETA and complete
                            shipment history from creation to delivery.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 transition hover:-translate-y-1 hover:border-blue-500/30">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                            🛰️
                        </div>

                        <h3 class="mt-5 text-lg font-semibold">
                            Live Fleet Tracking
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Monitor vehicle locations in real time with live WebSocket
                            updates and interactive maps.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 transition hover:-translate-y-1 hover:border-blue-500/30">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500/10 text-violet-400">
                            📍
                        </div>

                        <h3 class="mt-5 text-lg font-semibold">
                            Geofencing
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Use geographic boundaries and PostGIS-powered spatial
                            queries to monitor operational zones.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 transition hover:-translate-y-1 hover:border-blue-500/30">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400">
                            🚚
                        </div>

                        <h3 class="mt-5 text-lg font-semibold">
                            Drivers & Vehicles
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Manage drivers, vehicles, assignments, availability and
                            operational status in one place.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 transition hover:-translate-y-1 hover:border-blue-500/30">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500/10 text-rose-400">
                            💳
                        </div>

                        <h3 class="mt-5 text-lg font-semibold">
                            Payments & Ratings
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Handle shipment payments, delivery ratings and driver
                            performance with ownership controls.
                        </p>
                    </div>


                    <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 transition hover:-translate-y-1 hover:border-blue-500/30">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-400">
                            🔐
                        </div>

                        <h3 class="mt-5 text-lg font-semibold">
                            Secure Operations
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Role-based authorization, private channels, audit logs and
                            protected resources keep operations secure.
                        </p>
                    </div>

                </div>

            </div>

        </section>


        {{-- CTA --}}
        <section class="mx-auto max-w-7xl px-6 py-24 lg:px-8">

            <div class="relative overflow-hidden rounded-3xl border border-blue-500/20 bg-blue-600/10 px-6 py-14 text-center sm:px-12">

                <div class="absolute left-1/2 top-0 -z-10 h-40 w-96 -translate-x-1/2 rounded-full bg-blue-500/20 blur-3xl"></div>

                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-400">
                    Ready to operate smarter?
                </p>

                <h2 class="mx-auto mt-4 max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">
                    Bring your entire logistics operation together.
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-slate-400">
                    Track shipments, manage your fleet and keep every delivery
                    moving with GeoLogistics.
                </p>

                <div class="mt-8">

                    @auth
                        <a
                            href="/dashboard"
                            class="inline-flex rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200"
                        >
                            Go to dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                        >
                            Get started
                        </a>
                    @endauth

                </div>

            </div>

        </section>

    </main>


    {{-- Footer --}}
    <footer class="border-t border-white/5">

        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">

            <div>
                <span class="font-semibold text-slate-300">
                    GeoLogistics
                </span>

                <span class="ml-2">
                    Smart logistics & fleet management.
                </span>
            </div>

            <div>
                Built with Laravel · Livewire · PostGIS · Reverb
            </div>

        </div>

    </footer>

</body>
</html>
<?php

use Livewire\Component;

new class extends Component
{
};
?>
<style>
    .docs-page {
        color: #0f172a;
    }

    .docs-page p {
        color: #475569;
        line-height: 1.75;
    }

    .docs-page section h2 {
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .docs-page section h3 {
        color: #1e293b;
    }

    .docs-page .docs-card {
        color: #334155;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.25rem;
    }

    .docs-page .docs-card h3 {
        color: #0f172a;
        font-weight: 700;
    }

    .docs-page .docs-card p {
        color: #475569;
        font-size: 0.875rem;
    }

    .docs-page pre {
        background: #0f172a !important;
        color: #f8fafc !important;
    }

    .docs-page pre code {
        color: #f8fafc !important;
        font-size: 0.875rem;
    }

    .docs-page code {
        color: #334155;
    }
</style>
<div class="docs-page min-h-screen bg-slate-50 text-slate-900">

    <div class="mx-auto flex max-w-7xl">

        {{-- Sidebar --}}
        <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:block">
            <div class="sticky top-0 max-h-screen overflow-y-auto p-6">

                <a href="/" class="block">
                    <div class="text-xl font-bold text-slate-900">
                        GeoLogistics
                    </div>

                    <p class="mt-1 text-xs text-slate-500">
                        Documentation
                    </p>
                </a>

                <nav class="mt-8 space-y-1 text-sm">

                    <a href="#overview"
                       class="block rounded-lg bg-slate-100 px-3 py-2 font-medium text-slate-900">
                        Overview
                    </a>

                    <a href="#getting-started"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Getting Started
                    </a>

                    <a href="#architecture"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Architecture
                    </a>

                    <a href="#authentication"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Authentication
                    </a>

                    <a href="#users-roles"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Users & Roles
                    </a>

                    <a href="#shipments"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Shipments
                    </a>

                    <a href="#fleet"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Fleet Management
                    </a>

                    <a href="#realtime"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Real-Time Tracking
                    </a>

                    <a href="#geofencing"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Geofencing
                    </a>

                    <a href="#routing"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Routing
                    </a>

                    <a href="#payments"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Payments & Ratings
                    </a>

                    <a href="#notifications"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Notifications
                    </a>

                    <a href="#public-tracking"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Public Tracking
                    </a>

                    <a href="#testing"
                       class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Testing
                    </a>

                    <div class="my-4 border-t border-slate-200"></div>

                    <a href="/docs/api"
                       class="flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2 font-medium text-blue-700 hover:bg-blue-100">
                        <span>API Reference</span>
                        <span>↗</span>
                    </a>

                </nav>

            </div>
        </aside>

        {{-- Main Content --}}
        <main class="min-w-0 flex-1">

            {{-- Header --}}
            <header class="border-b border-slate-200 bg-white">
                <div class="px-6 py-8 sm:px-10 lg:px-12">

                    <div class="max-w-4xl">

                        <p class="text-sm font-medium text-blue-600">
                            GeoLogistics Documentation
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                            Real-Time Logistics & Fleet Tracking Platform
                        </h1>

                        <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">
                            Technical documentation for GeoLogistics, a Laravel-based
                            logistics platform for managing shipments, vehicles,
                            drivers, customers, geospatial data, and real-time fleet tracking.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">

                            <a
                                href="/docs/api"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                API Reference
                            </a>

                            <a
                                href="https://github.com/Bashar747/geologistics"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                GitHub
                            </a>

                        </div>

                    </div>

                </div>
            </header>

            {{-- Documentation --}}
            <div class="px-6 py-10 sm:px-10 lg:px-12">

                <div class="max-w-4xl space-y-16">

                    {{-- Overview --}}
                    <section id="overview" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Overview
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            GeoLogistics is a logistics and fleet management platform
                            built with Laravel. The application manages shipments,
                            vehicles, drivers, customers, payments, ratings,
                            notifications, geofences, and real-time vehicle locations.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">

                            <div class="docs-card">
                                <p class="text-sm font-medium text-slate-500">
                                    Backend
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">
                                    Laravel 13 · PHP 8.4
                                </p>
                            </div>

                            <div class="docs-card">
                                <p class="text-sm font-medium text-slate-500">
                                    Frontend
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">
                                    Livewire · Blade · Tailwind CSS
                                </p>
                            </div>

                            <div class="docs-card">
                                <p class="text-sm font-medium text-slate-500">
                                    Database
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">
                                    PostgreSQL · PostGIS
                                </p>
                            </div>

                            <div class="docs-card">
                                <p class="text-sm font-medium text-slate-500">
                                    Real-Time
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">
                                    Laravel Reverb · Echo
                                </p>
                            </div>

                        </div>

                    </section>

                    {{-- Getting Started --}}
                    <section id="getting-started" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Getting Started
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            GeoLogistics can be run locally using the Laravel development
                            server together with Reverb, the queue worker, and Vite.
                        </p>

                        <h3 class="mt-6 text-lg font-semibold text-slate-900">
                            Development Services
                        </h3>

                        <div class="mt-4 overflow-hidden rounded-xl bg-slate-950">
    <pre class="overflow-x-auto p-5 text-sm leading-6 text-white"><code>php artisan serve --port=8001
php artisan reverb:start
php artisan queue:work
npm run dev</code></pre>
</div>

                        <p class="mt-4 text-sm text-slate-500">
                            The local application is available at
                            <code class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-700">
                                http://127.0.0.1:8001
                            </code>.
                        </p>

                    </section>

                    {{-- Architecture --}}
                    <section id="architecture" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Architecture
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            The application follows Laravel's MVC architecture with
                            Livewire Volt pages for the web interface, API controllers
                            and resources for API access, services for reusable business
                            logic, policies for authorization, and events for real-time
                            communication.
                        </p>

                        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">

                            <div class="grid divide-y divide-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0">

                                <div class="p-5">
                                    <h3 class="font-semibold text-slate-900">
                                        Web Layer
                                    </h3>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Livewire Volt pages and Blade views provide the
                                        application interface.
                                    </p>
                                </div>

                                <div class="p-5">
                                    <h3 class="font-semibold text-slate-900">
                                        API Layer
                                    </h3>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        API controllers and resources expose application
                                        functionality through Laravel routes.
                                    </p>
                                </div>

                                <div class="border-t border-slate-200 p-5">
                                    <h3 class="font-semibold text-slate-900">
                                        Services
                                    </h3>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Business logic such as routing, pricing,
                                        dispatching, and geofencing is organized
                                        in dedicated services.
                                    </p>
                                </div>

                                <div class="border-t border-slate-200 p-5">
                                    <h3 class="font-semibold text-slate-900">
                                        Data & Events
                                    </h3>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        PostgreSQL/PostGIS stores application and
                                        geospatial data while Laravel events handle
                                        real-time updates.
                                    </p>
                                </div>

                            </div>

                        </div>

                    </section>

                    {{-- Authentication --}}
                    <section id="authentication" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Authentication & Authorization
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            API authentication uses Laravel Sanctum. Protected API
                            endpoints require a Bearer token.
                        </p>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-5">

                            <h3 class="font-semibold text-slate-900">
                                Authorization
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Access is controlled through roles, policies, and
                                resource-level authorization checks.
                            </p>

                        </div>

                       <div class="mt-5 overflow-hidden rounded-xl bg-slate-950">
    <pre class="overflow-x-auto p-5 text-sm leading-6 text-white"><code>Authorization: Bearer YOUR_TOKEN</code></pre>
</div>

                    </section>

                    {{-- Users --}}
                    <section id="users-roles" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Users & Roles
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            GeoLogistics uses role-based access control for the main
                            application user types.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">

                            <div class="docs-card">
                                <h3 class="font-semibold text-slate-900">Admin</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Full administrative access to the platform.
                                </p>
                            </div>

                            <div class="docs-card">
                                <h3 class="font-semibold text-slate-900">Dispatcher</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Operational access to shipments, vehicles,
                                    drivers, and fleet management.
                                </p>
                            </div>

                           <div class="docs-card">
                                <h3 class="font-semibold text-slate-900">Driver</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Access to assigned vehicles and shipments,
                                    including location updates.
                                </p>
                            </div>

                            <div class="docs-card">
                                <h3 class="font-semibold text-slate-900">Customer</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Access to their shipments, tracking,
                                    payments, and ratings.
                                </p>
                            </div>

                        </div>

                    </section>

                    {{-- Shipments --}}
                    <section id="shipments" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Shipments
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Shipments contain pickup and dropoff locations,
                            customer information, items, status history,
                            vehicle assignments, payments, and ratings.
                        </p>

                        <h3 class="mt-6 text-lg font-semibold text-slate-900">
                            Shipment Lifecycle
                        </h3>

                        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">

                            @foreach (['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'] as $status)

                                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 font-medium text-slate-700">
                                    {{ $status }}
                                </span>

                                @if (!$loop->last)
                                    <span class="text-slate-400">→</span>
                                @endif

                            @endforeach

                        </div>

                        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">

                            <h3 class="font-semibold text-slate-900">
                                Shipment Data
                            </h3>

                            <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                                <li>• Tracking number</li>
                                <li>• Pickup and dropoff coordinates</li>
                                <li>• Shipment items</li>
                                <li>• Estimated arrival</li>
                                <li>• Total amount</li>
                                <li>• Status history</li>
                                <li>• Assigned vehicle</li>
                                <li>• Payment and rating information</li>
                            </ul>

                        </div>

                    </section>

                    {{-- Fleet --}}
                    <section id="fleet" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Fleet Management
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Vehicles can be managed through the web application
                            and API. Vehicles contain their current status,
                            latest location, and active driver assignment.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">

                            @foreach (['idle', 'in_transit', 'maintenance', 'offline'] as $status)

                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <span class="text-sm font-semibold capitalize text-slate-900">
                                        {{ str_replace('_', ' ', $status) }}
                                    </span>
                                </div>

                            @endforeach

                        </div>

                    </section>

                    {{-- Realtime --}}
                    <section id="realtime" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Real-Time Tracking
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Real-time vehicle tracking uses Laravel Reverb,
                            Laravel Echo, private channels, and the
                            <code class="rounded bg-slate-100 px-1.5 py-0.5">
                                VehicleLocationUpdated
                            </code>
                            broadcast event.
                        </p>

                        <div class="mt-6 overflow-hidden rounded-xl bg-slate-950">
                            <pre class="overflow-x-auto p-5 text-sm leading-6 text-slate-200"><code>Vehicle
   ↓
Location Update
   ↓
VehicleLocationUpdated
   ↓
Laravel Reverb
   ↓
Private Channel
   ↓
Laravel Echo
   ↓
Live Map</code></pre>
                        </div>

                        <p class="mt-5 text-sm leading-6 text-slate-600">
                            Vehicle location updates can be displayed on the fleet
                            and vehicle tracking maps without refreshing the page.
                        </p>

                    </section>

                    {{-- Geofencing --}}
                    <section id="geofencing" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Geofencing
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            GeoLogistics uses PostGIS spatial operations to work
                            with geofences and vehicle locations.
                        </p>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-5">

                            <h3 class="font-semibold text-slate-900">
                                Supported Geofence Types
                            </h3>

                            <div class="mt-4 flex flex-wrap gap-2">

                                @foreach (['warehouse', 'restricted_zone', 'delivery_area'] as $type)

                                    <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700">
                                        {{ str_replace('_', ' ', $type) }}
                                    </span>

                                @endforeach

                            </div>

                        </div>

                    </section>

                    {{-- Routing --}}
                    <section id="routing" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Routing
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Route calculation is handled through a dedicated
                            RoutingService using GraphHopper.
                        </p>

                        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">

                            <h3 class="font-semibold text-slate-900">
                                Route Flow
                            </h3>

                            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">

                                <span class="rounded-lg bg-slate-100 px-3 py-2">
                                    Vehicle
                                </span>

                                <span class="text-slate-400">→</span>

                                <span class="rounded-lg bg-slate-100 px-3 py-2">
                                    Pickup
                                </span>

                                <span class="text-slate-400">→</span>

                                <span class="rounded-lg bg-slate-100 px-3 py-2">
                                    Dropoff
                                </span>

                            </div>

                        </div>

                    </section>

                    {{-- Payments --}}
                    <section id="payments" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Payments & Ratings
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Customers can create payments for their shipments.
                            Payment methods include cash, card, and wallet.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-3">

                            @foreach (['cash', 'card', 'wallet'] as $method)

                                <div class="rounded-xl border border-slate-200 bg-white p-5 text-center">
                                    <p class="font-semibold capitalize text-slate-900">
                                        {{ $method }}
                                    </p>
                                </div>

                            @endforeach

                        </div>

                        <p class="mt-5 text-sm leading-6 text-slate-600">
                            Customers can also rate delivered shipments using
                            a score from 1 to 5 with an optional comment.
                        </p>

                    </section>

                    {{-- Notifications --}}
                    <section id="notifications" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Notifications
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Notifications are stored in the application and
                            support multiple delivery channels.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">

                            @foreach (['SMS', 'Push', 'Email'] as $channel)

                                <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                    {{ $channel }}
                                </span>

                            @endforeach

                        </div>

                    </section>

                    {{-- Public Tracking --}}
                    <section id="public-tracking" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Public Tracking
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Customers can track shipments using their tracking
                            number without exposing customer personal data.
                        </p>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-5">

                            <p class="text-sm text-slate-500">
                                Tracking URL
                            </p>

                            <code class="mt-2 block break-all rounded-lg bg-slate-950 p-4 text-sm text-slate-200">
                                /track/{trackingNumber}
                            </code>

                        </div>

                    </section>

                    {{-- Testing --}}
                    <section id="testing" class="scroll-mt-8">

                        <h2 class="text-2xl font-bold text-slate-900">
                            Testing
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            The project includes feature and unit tests covering
                            workflows such as customers, drivers, geofencing,
                            real-time tracking, and proof of delivery.
                        </p>

                        <div class="mt-5 overflow-hidden rounded-xl bg-slate-950">
                            <pre class="overflow-x-auto p-5 text-sm leading-6 text-slate-200"><code>php artisan test</code></pre>
                        </div>

                    </section>

                    {{-- Footer --}}
                    <section class="border-t border-slate-200 pt-8">

                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-6">

                            <h2 class="font-semibold text-slate-900">
                                API Documentation
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                For the complete interactive API documentation,
                                including available API endpoints and schemas,
                                open the API Reference.
                            </p>

                            <a
                                href="/docs/api"
                                class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                Open API Reference →
                            </a>

                        </div>

                    </section>

                </div>

            </div>

        </main>

    </div>

</div>
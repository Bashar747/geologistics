<?php

use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Component;

new class extends Component
{
    public function getStatsProperty(): array
    {
        return [
            'total_shipments' => Shipment::count(),

            'active_shipments' => Shipment::whereIn('status', [
                'assigned',
                'picked_up',
                'in_transit',
            ])->count(),

            'available_vehicles' => Vehicle::where('status', 'idle')->count(),

            'available_drivers' => User::where('role', 'driver')
                ->whereHas('driverProfile', function ($q) {
                    $q->where('status', 'available');
                })
                ->count(),
        ];
    }

    public function getFleetVehiclesProperty()
    {
        return Vehicle::query()
            ->with([
                'currentAssignment.driver:id,name',
            ])
            ->latest('updated_at')
            ->get();
    }

    public function getFleetMapVehiclesProperty(): array
    {
        return $this->fleetVehicles
            ->map(function ($vehicle) {
                $shipment = $vehicle->shipments()
                    ->whereIn('status', [
                        'assigned',
                        'picked_up',
                        'in_transit',
                    ])
                    ->latest()
                    ->first();

                return [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'driver_name' => $vehicle->currentAssignment?->driver?->name,
                    'latitude' => $vehicle->last_location?->getLatitude(),
                    'longitude' => $vehicle->last_location?->getLongitude(),
                    'status' => $vehicle->status,
                    'current_shipment_number' => $shipment?->tracking_number,
                ];
            })
            ->values()
            ->all();
    }

    public function getRecentShipmentsProperty()
    {
        return Shipment::query()
            ->with(['customer', 'vehicle'])
            ->latest()
            ->limit(5)
            ->get();
    }
};
?>

@if (auth()->check() && auth()->user()->role === 'customer')

    @php
        $customer = auth()->user();

        $customerShipments = Shipment::where(
            'customer_id',
            $customer->id
        );

        $counts = [
            'total' => (clone $customerShipments)->count(),

            'active' => (clone $customerShipments)
                ->whereIn('status', [
                    'assigned',
                    'picked_up',
                    'in_transit',
                ])
                ->count(),

            'pending' => (clone $customerShipments)
                ->where('status', 'pending')
                ->count(),

            'delivered' => (clone $customerShipments)
                ->where('status', 'delivered')
                ->count(),
        ];

        $recentShipments = (clone $customerShipments)
            ->with(['vehicle'])
            ->latest()
            ->limit(5)
            ->get();
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Customer Dashboard
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Welcome back, {{ $customer->name }}. Here is a quick overview of your shipments.
            </p>
        </div>


        {{-- Shipment Overview --}}
       <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5">

            {{-- Total --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Total Shipments
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $counts['total'] }}
                </p>
            </div>


            {{-- Active --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Active Shipments
                </p>

                <p class="mt-2 text-3xl font-bold text-blue-600">
                    {{ $counts['active'] }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Assigned, picked up or in transit
                </p>
            </div>


            {{-- Pending --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Pending
                </p>

                <p class="mt-2 text-3xl font-bold text-amber-600">
                    {{ $counts['pending'] }}
                </p>
            </div>


            {{-- Delivered --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Delivered
                </p>

                <p class="mt-2 text-3xl font-bold text-emerald-600">
                    {{ $counts['delivered'] }}
                </p>
            </div>

        </div>


        {{-- Recent Shipments --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">

            <div class="px-6 py-5 border-b border-slate-200">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Your Recent Shipments
                        </h2>

                        <p class="text-sm text-slate-500 mt-1">
                            Your latest shipment activity.
                        </p>
                    </div>

                    <a
                        href="/shipments"
                        wire:navigate
                        class="text-sm font-semibold text-blue-600 hover:text-blue-700"
                    >
                        View all
                    </a>

                </div>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>
                            <th class="px-6 py-4">
                                Tracking Number
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                            <th class="px-6 py-4">
                                Vehicle
                            </th>

                            <th class="px-6 py-4">
                                Est. Arrival
                            </th>

                            <th class="px-6 py-4 text-right">
                                Action
                            </th>
                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse ($recentShipments as $shipment)

                            @php
                                $statusClass = match ($shipment->status) {
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'assigned' => 'bg-blue-100 text-blue-700',
                                    'picked_up' => 'bg-purple-100 text-purple-700',
                                    'in_transit' => 'bg-indigo-100 text-indigo-700',
                                    'delivered' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp

                            <tr class="hover:bg-slate-50 transition">

                                <td class="px-6 py-4">
                                    <a
                                        href="/shipments/{{ $shipment->id }}"
                                        wire:navigate
                                        class="font-mono font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                                    >
                                        {{ $shipment->tracking_number }}
                                    </a>
                                </td>

                                <td class="px-6 py-4">

                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClass }}"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                    </span>

                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    {{ $shipment->vehicle?->plate_number ?? 'Unassigned' }}
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    {{ $shipment->estimated_arrival?->format('M d, Y · h:i A') ?? '—' }}
                                </td>

                                <td class="px-6 py-4 text-right">

                                    <a
                                        href="/shipments/{{ $shipment->id }}"
                                        wire:navigate
                                        class="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                    >
                                        View →
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center"
                                >
                                    <p class="text-sm font-medium text-slate-600">
                                        No shipments found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-400">
                                        Your shipments will appear here once created.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


@elseif (auth()->check() && auth()->user()->role === 'driver')

    @php
        $driver = auth()->user();

        $activeAssignment = $driver
            ->vehicleAssignments()
            ->where('is_active', true)
            ->with('vehicle')
            ->first();

        $assignedVehicle = $activeAssignment?->vehicle;

        $activeShipment = $assignedVehicle
            ? $assignedVehicle
                ->shipments()
                ->whereIn('status', [
                    'assigned',
                    'picked_up',
                    'in_transit',
                ])
                ->latest()
                ->first()
            : null;
    @endphp


    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Driver Dashboard
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Welcome back, {{ $driver->name }}. Manage your vehicle and active shipments.
            </p>
        </div>


        {{-- Vehicle & Shipment --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Assigned Vehicle --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">

                <div class="flex items-center gap-3 mb-5">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                        <span class="text-lg">🚚</span>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Assigned Vehicle
                        </h2>

                        <p class="text-xs text-slate-500">
                            Your current vehicle
                        </p>
                    </div>

                </div>


                @if ($assignedVehicle)

                    <div class="space-y-3 text-sm">

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">
                                Plate Number
                            </span>

                            <span class="font-semibold text-slate-900">
                                {{ $assignedVehicle->plate_number }}
                            </span>
                        </div>


                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">
                                Model / Type
                            </span>

                            <span class="font-medium text-slate-700">
                                {{ $assignedVehicle->model ?? 'N/A' }}
                                ({{ $assignedVehicle->type ?? 'N/A' }})
                            </span>
                        </div>


                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">
                                Vehicle Status
                            </span>

                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                {{ ucfirst(str_replace('_', ' ', $assignedVehicle->status)) }}
                            </span>
                        </div>


                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">
                                Last Location
                            </span>

                            <span class="font-mono text-xs text-slate-600">
                                @if ($assignedVehicle->last_location)

                                    {{ number_format($assignedVehicle->last_location->getLatitude(), 4) }},
                                    {{ number_format($assignedVehicle->last_location->getLongitude(), 4) }}

                                @else

                                    Not recorded

                                @endif
                            </span>
                        </div>

                    </div>

                @else

                    <div class="py-8 text-center">
                        <p class="text-sm text-slate-400">
                            No active vehicle is currently assigned to you.
                        </p>
                    </div>

                @endif

            </div>


            {{-- Active Shipment --}}
           <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">

                <div class="flex items-center gap-3 mb-5">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                        <span class="text-lg">📦</span>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Current Active Shipment
                        </h2>

                        <p class="text-xs text-slate-500">
                            Shipment currently assigned to your vehicle
                        </p>
                    </div>

                </div>


                @if ($activeShipment)

                    <div class="space-y-3 text-sm">

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">
                                Tracking Number
                            </span>

                            <a
                                href="/shipments/{{ $activeShipment->id }}"
                                wire:navigate
                                class="font-mono font-semibold text-blue-600 hover:underline"
                            >
                                {{ $activeShipment->tracking_number }}
                            </a>
                        </div>


                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">
                                Status
                            </span>

                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                {{ ucfirst(str_replace('_', ' ', $activeShipment->status)) }}
                            </span>
                        </div>


                        @if ($activeShipment->estimated_arrival)

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-500">
                                    Est. Arrival
                                </span>

                                <span class="font-medium text-slate-700">
                                    {{ $activeShipment->estimated_arrival->format('M d, Y · h:i A') }}
                                </span>
                            </div>

                        @endif


                        <div class="pt-3 border-t border-slate-100">

                            <a
                                href="/shipments/{{ $activeShipment->id }}"
                                wire:navigate
                                class="block w-full rounded-xl bg-blue-600 py-2.5 text-center text-xs font-semibold text-white transition hover:bg-blue-700"
                            >
                                View & Manage Shipment
                            </a>

                        </div>

                    </div>

                @else

                    <div class="py-8 text-center">
                        <p class="text-sm text-slate-400">
                            No active shipment is currently assigned to your vehicle.
                        </p>
                    </div>

                @endif

            </div>

        </div>


        {{-- Assigned Shipments --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">

            <div class="px-6 py-5 border-b border-slate-200">

                <h2 class="text-lg font-semibold text-slate-900">
                    My Assigned Shipments
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Shipments currently assigned to your active vehicle.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">

                            <th class="px-6 py-4">
                                Tracking Number
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                            <th class="px-6 py-4">
                                Customer
                            </th>

                            <th class="px-6 py-4 text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse (
                            $driver
                                ->vehicleAssignments()
                                ->where('is_active', true)
                                ->with('vehicle.shipments.customer')
                                ->get() as $assignment
                        )

                            @foreach ($assignment->vehicle->shipments as $shipment)

                                <tr class="hover:bg-slate-50 transition">

                                    <td class="px-6 py-4">

                                        <a
                                            href="/shipments/{{ $shipment->id }}"
                                            wire:navigate
                                            class="font-mono font-semibold text-blue-600 hover:underline"
                                        >
                                            {{ $shipment->tracking_number }}
                                        </a>

                                    </td>


                                    <td class="px-6 py-4">

                                        @php
                                            $statusClass = match ($shipment->status) {
                                                'pending' => 'bg-amber-100 text-amber-700',
                                                'assigned' => 'bg-blue-100 text-blue-700',
                                                'picked_up' => 'bg-purple-100 text-purple-700',
                                                'in_transit' => 'bg-indigo-100 text-indigo-700',
                                                'delivered' => 'bg-emerald-100 text-emerald-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp

                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                        </span>

                                    </td>


                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $shipment->customer?->name ?? '—' }}
                                    </td>


                                    <td class="px-6 py-4 text-right">

                                        <a
                                            href="/shipments/{{ $shipment->id }}"
                                            wire:navigate
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                        >
                                            Open →
                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        @empty

                            <tr>

                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center"
                                >
                                    <p class="text-sm font-medium text-slate-600">
                                        No assigned shipments found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-400">
                                        Assigned shipments will appear here.
                                    </p>
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


@else

    {{-- Admin / Dispatcher Dashboard --}}
    <div class="space-y-6">

        {{-- Header --}}
        <div>
    <h1 class="text-xl sm:text-2xl font-bold text-slate-900">
        Dashboard
    </h1>

    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
        A quick overview of your logistics operations.
    </p>
</div>


        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5">
            {{-- Total Shipments --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Total Shipments
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            {{ $this->stats['total_shipments'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            All shipments
                        </p>
                    </div>


                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">

                        <svg
                            class="w-6 h-6 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"
                            />

                            <circle cx="7" cy="19" r="1.5"/>
                            <circle cx="18" cy="19" r="1.5"/>
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Active Shipments --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Active Shipments
                        </p>

                        <p class="mt-2 text-3xl font-bold text-indigo-600">
                            {{ $this->stats['active_shipments'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Currently in progress
                        </p>
                    </div>


                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">

                        <svg
                            class="w-6 h-6 text-indigo-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke-width="1.8"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 7v5l3 2"
                            />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Available Drivers --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Available Drivers
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            {{ $this->stats['available_drivers'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Ready for assignment
                        </p>
                    </div>


                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">

                        <svg
                            class="w-6 h-6 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                cx="12"
                                cy="8"
                                r="3"
                                stroke-width="1.8"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M5 20c.8-3.5 3-5 7-5s6.2 1.5 7 5"
                            />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Available Vehicles --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Available Vehicles
                        </p>

                        <p class="mt-2 text-3xl font-bold text-violet-600">
                            {{ $this->stats['available_vehicles'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Ready for assignment
                        </p>
                    </div>


                    <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center">

                        <svg
                            class="w-6 h-6 text-violet-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M5 17h14l-1-7H6l-1 7z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M7 10l1.5-4h7L17 10"
                            />

                            <circle cx="8" cy="18" r="1.5"/>
                            <circle cx="16" cy="18" r="1.5"/>
                        </svg>

                    </div>

                </div>

            </div>

        </div>


        {{-- Live Fleet Map --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-5 border-b border-slate-200">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Live Fleet Map
                        </h2>

                        <p class="text-sm text-slate-500 mt-1">
                            Real-time vehicle locations across your fleet.
                        </p>
                    </div>


                    <div class="flex items-center gap-2 text-xs text-slate-500">

                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>

                        Live

                    </div>

                </div>

            </div>


          <div
    wire:ignore
    id="admin-fleet-map"
    class="w-full h-[400px] md:h-[520px]"
    style="height: 400px; min-height: 400px;"
></div>

        </div>


        {{-- Recent Shipments --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">

            <div class="px-6 py-5 border-b border-slate-200">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Recent Shipments
                        </h2>

                        <p class="text-sm text-slate-500 mt-1">
                            A quick look at the latest shipments.
                        </p>
                    </div>


                    <a
                        href="/shipments"
                        wire:navigate
                        class="text-sm font-semibold text-blue-600 hover:text-blue-700"
                    >
                        View all
                    </a>

                </div>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">

                            <th class="px-6 py-4">
                                Tracking Number
                            </th>

                            <th class="px-6 py-4">
                                Customer
                            </th>

                            <th class="px-6 py-4">
                                Vehicle
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse ($this->recentShipments as $shipment)

                            @php
                                $statusClass = match ($shipment->status) {
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'assigned' => 'bg-blue-100 text-blue-700',
                                    'picked_up' => 'bg-purple-100 text-purple-700',
                                    'in_transit' => 'bg-indigo-100 text-indigo-700',
                                    'delivered' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp


                            <tr class="hover:bg-slate-50 transition">

                                <td class="px-6 py-4">

                                    <a
                                        href="/shipments/{{ $shipment->id }}"
                                        wire:navigate
                                        class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                                    >
                                        {{ $shipment->tracking_number }}
                                    </a>

                                </td>


                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $shipment->customer?->name ?? '—' }}
                                </td>


                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $shipment->vehicle?->plate_number ?? 'Unassigned' }}
                                </td>


                                <td class="px-6 py-4">

                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClass }}"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                    </span>

                                </td>


                                <td class="px-6 py-4 text-right">

                                    <a
                                        href="/shipments/{{ $shipment->id }}"
                                        wire:navigate
                                        class="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                    >
                                        View →
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center"
                                >

                                    <p class="text-sm font-medium text-slate-600">
                                        No shipments found.
                                    </p>

                                    <p class="text-sm text-slate-400 mt-1">
                                        Shipments will appear here once created.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endif


@script
<script>
    const adminFleetVehicles = @json($this->fleetMapVehicles);

    let adminFleetMap = null;
    let adminFleetMarkers = {};

    function initAdminFleetMap() {
        const mapEl = document.getElementById('admin-fleet-map');

        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        if (adminFleetMap) {
            adminFleetMap.remove();
            adminFleetMap = null;
            adminFleetMarkers = {};
        } else if (mapEl._leaflet_id) {
            mapEl._leaflet_id = null;
            mapEl.innerHTML = '';
        }

        adminFleetMap = L.map(mapEl, {
            zoomControl: true,
            scrollWheelZoom: true,
        }).setView([33.3152, 44.3661], 10);

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }
        ).addTo(adminFleetMap);

        adminFleetVehicles.forEach(vehicle => {
            if (vehicle.latitude && vehicle.longitude) {
                addOrUpdateAdminFleetMarker(vehicle);
            }
        });

        fitAdminFleetMap();

        setTimeout(() => {
            if (adminFleetMap) {
                adminFleetMap.invalidateSize();
            }
        }, 250);

        subscribeToAdminFleet();
    }

    function fitAdminFleetMap() {
        if (!adminFleetMap) {
            return;
        }

        const markers = Object.values(adminFleetMarkers);

        if (markers.length === 0) {
            return;
        }

        const group = new L.featureGroup(markers);

        adminFleetMap.fitBounds(
            group.getBounds().pad(0.1)
        );
    }

    function addOrUpdateAdminFleetMarker(vehicle) {
        if (
            !adminFleetMap ||
            vehicle.latitude === null ||
            vehicle.longitude === null ||
            vehicle.latitude === undefined ||
            vehicle.longitude === undefined
        ) {
            return;
        }

        const latitude = Number(vehicle.latitude);
        const longitude = Number(vehicle.longitude);

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return;
        }

        const color =
            vehicle.status === 'in_transit'
                ? '#2563eb'
                : vehicle.status === 'idle'
                    ? '#10b981'
                    : '#64748b';

        const icon = L.divIcon({
            className: 'admin-fleet-marker',
            html: `
                <div style="
                    width:36px;
                    height:36px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:50%;
                    background:${color};
                    border:3px solid white;
                    box-shadow:0 3px 10px rgba(0,0,0,0.2);
                    font-size:16px;
                ">
                    🚚
                </div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
        });

        const popupContent = `
            <div style="min-width:170px">
                <strong>${escapeAdminFleetHtml(vehicle.plate_number || 'Vehicle')}</strong><br>
                <span style="color:#64748b">
                    Driver: ${escapeAdminFleetHtml(vehicle.driver_name || 'Unassigned')}
                </span><br>
                <span style="color:#64748b">
                    Status: ${escapeAdminFleetHtml(
                        String(vehicle.status || '').replaceAll('_', ' ')
                    )}
                </span>
                ${
                    vehicle.current_shipment_number
                        ? `<br><span style="color:#2563eb">
                            Shipment: ${escapeAdminFleetHtml(vehicle.current_shipment_number)}
                           </span>`
                        : ''
                }
            </div>
        `;

        if (adminFleetMarkers[vehicle.id]) {
            adminFleetMarkers[vehicle.id].setLatLng([
                latitude,
                longitude,
            ]);

            adminFleetMarkers[vehicle.id].setIcon(icon);

            if (adminFleetMarkers[vehicle.id].getPopup()) {
                adminFleetMarkers[vehicle.id]
                    .getPopup()
                    .setContent(popupContent);
            }
        } else {
            adminFleetMarkers[vehicle.id] = L.marker(
                [latitude, longitude],
                { icon }
            )
                .addTo(adminFleetMap)
                .bindPopup(popupContent);
        }
    }

    function escapeAdminFleetHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function subscribeToAdminFleet() {
        if (!window.Echo) {
            console.warn('Echo is not available.');
            return;
        }

        if (window.__adminFleetChannelSubscription) {
            window.Echo.leave('fleet');
            window.__adminFleetChannelSubscription = null;
        }

        window.__adminFleetChannelSubscription =
            window.Echo
                .private('fleet')
                .listen('.location.updated', (event) => {

                    const vehicle = {
                        id: event.vehicle_id,
                        plate_number: event.plate_number,
                        driver_name: event.driver_name,
                        latitude: event.latitude,
                        longitude: event.longitude,
                        status: event.status,
                        current_shipment_number:
                            event.current_shipment_number,
                    };

                    addOrUpdateAdminFleetMarker(vehicle);
                });
    }

    document.addEventListener(
        'livewire:navigated',
        initAdminFleetMap
    );

    document.addEventListener(
        'livewire:navigating',
        () => {

            if (
                window.__adminFleetChannelSubscription &&
                window.Echo
            ) {
                window.Echo.leave('fleet');
                window.__adminFleetChannelSubscription = null;
            }

            if (adminFleetMap) {
                adminFleetMap.remove();
                adminFleetMap = null;
                adminFleetMarkers = {};
            }
        }
    );

    initAdminFleetMap();
</script>
@endscript
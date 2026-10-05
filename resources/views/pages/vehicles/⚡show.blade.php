<?php

use App\Models\Vehicle;
use Livewire\Component;

use Livewire\WithPagination;
new class extends Component
{


     use WithPagination;

    public Vehicle $vehicle;
    public int $historyPerPage = 10;


   
    public function mount(Vehicle $vehicle): void
    {

         $this->authorize('view', $vehicle);
        $this->vehicle = $vehicle->load([
            'currentAssignment.driver:id,name,phone',
            'shipments' => function ($query) {
                $query
                    ->whereIn('status', ['assigned', 'picked_up'])
                    ->latest()
                    ->limit(5);
            },
        ]);
    }

    public function getStatusLabelProperty(): string
    {
        return match ($this->vehicle->status) {
            'idle' => 'Idle',
            'in_transit' => 'In Transit',
            'maintenance' => 'Maintenance',
            'offline' => 'Offline',
            default => ucfirst(str_replace('_', ' ', $this->vehicle->status)),
        };
    }

    public function getStatusClassesProperty(): string
    {
        return match ($this->vehicle->status) {
            'idle' =>
                'bg-emerald-100 text-emerald-700',

            'in_transit' =>
                'bg-blue-100 text-blue-700',

            'maintenance' =>
                'bg-amber-100 text-amber-700',

            'offline' =>
                'bg-slate-200 text-slate-700',

            default =>
                'bg-slate-100 text-slate-700',
        };
    }
    public function getLocationHistoryProperty()
{
    return $this->vehicle
        ->locationLogs()
        ->latest('recorded_at')
        ->paginate(
            $this->historyPerPage,
            pageName: 'location-history'
        );
}
};
?>

<div class="space-y-6">

    {{-- ============================================================= --}}
    {{-- Header --}}
    {{-- ============================================================= --}}

    <div
        class="flex flex-col gap-4
               sm:flex-row sm:items-center
               sm:justify-between"
    >

        <div>

            <div class="flex items-center gap-3">

                <a
                    href="{{ route('vehicles.index') }}"
                    wire:navigate
                    class="inline-flex h-9 w-9
                           items-center justify-center
                           rounded-lg
                           border border-slate-200
                           text-slate-500
                           transition
                           hover:bg-slate-50
                           hover:text-slate-700"
                    aria-label="Back to vehicles"
                >
                    ←
                </a>

                <div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ $vehicle->plate_number }}
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Vehicle details and current activity.
                    </p>

                </div>

            </div>

        </div>


        {{-- Edit --}}
        <a
            href="{{ route('vehicles.edit', $vehicle) }}"
            wire:navigate
            class="inline-flex items-center
                   justify-center gap-2
                   rounded-xl
                   bg-blue-600
                   px-5 py-3
                   text-sm font-semibold
                   text-white
                   transition
                   hover:bg-blue-700"
        >
            Edit Vehicle
        </a>

    </div>


    {{-- ============================================================= --}}
    {{-- Vehicle Overview --}}
    {{-- ============================================================= --}}

    <div
        class="overflow-hidden
               rounded-2xl
               border border-slate-200
               bg-white"
    >

        <div
            class="border-b border-slate-200
                   px-6 py-5"
        >

            <div
                class="flex flex-col gap-4
                       sm:flex-row
                       sm:items-center
                       sm:justify-between"
            >

                <div>

                    <h2 class="text-base font-semibold text-slate-900">
                        Vehicle Overview
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Basic information about this vehicle.
                    </p>

                </div>


                {{-- Status --}}
                <span
                    class="inline-flex w-fit
                           rounded-full
                           px-3 py-1
                           text-xs font-semibold
                           {{ $this->statusClasses }}"
                >
                    {{ $this->statusLabel }}
                </span>

            </div>

        </div>


        <div
            class="grid grid-cols-1
                   gap-6
                   p-6
                   sm:grid-cols-2
                   lg:grid-cols-3"
        >

            {{-- Plate Number --}}
            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-slate-400"
                >
                    Plate Number
                </p>

                <p
                    class="mt-2 text-sm
                           font-semibold
                           text-slate-900"
                >
                    {{ $vehicle->plate_number }}
                </p>

            </div>


            {{-- Model --}}
            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-slate-400"
                >
                    Model
                </p>

                <p
                    class="mt-2 text-sm
                           font-semibold
                           text-slate-900"
                >
                    {{ $vehicle->model ?: 'Not specified' }}
                </p>

            </div>


            {{-- Type --}}
            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-slate-400"
                >
                    Vehicle Type
                </p>

                <p
                    class="mt-2 text-sm
                           font-semibold
                           text-slate-900"
                >
                    {{ $vehicle->type ?: 'Not specified' }}
                </p>

            </div>


            {{-- Status --}}
            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-slate-400"
                >
                    Status
                </p>

                <p
                    class="mt-2 text-sm
                           font-semibold
                           text-slate-900"
                >
                    {{ $this->statusLabel }}
                </p>

            </div>


            {{-- Created --}}
            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-slate-400"
                >
                    Added
                </p>

                <p
                    class="mt-2 text-sm
                           font-semibold
                           text-slate-900"
                >
                    {{ $vehicle->created_at?->format('M d, Y') ?: '—' }}
                </p>

            </div>


            {{-- Last Updated --}}
            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wider
                           text-slate-400"
                >
                    Last Updated
                </p>

                <p
                    class="mt-2 text-sm
                           font-semibold
                           text-slate-900"
                >
                    {{ $vehicle->updated_at?->format('M d, Y H:i') ?: '—' }}
                </p>

            </div>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- Driver + Location --}}
    {{-- ============================================================= --}}

    <div
        class="grid grid-cols-1 gap-6
               lg:grid-cols-2"
    >

        {{-- Current Driver --}}
        <div
            class="rounded-2xl
                   border border-slate-200
                   bg-white"
        >

            <div
                class="border-b border-slate-200
                       px-6 py-5"
            >

                <h2 class="text-base font-semibold text-slate-900">
                    Current Driver
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Driver currently assigned to this vehicle.
                </p>

            </div>


            <div class="p-6">

                @if ($vehicle->currentAssignment?->driver)

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12
                                   shrink-0
                                   items-center justify-center
                                   rounded-full
                                   bg-blue-100
                                   text-lg font-bold
                                   text-blue-700"
                        >
                            {{ strtoupper(substr($vehicle->currentAssignment->driver->name, 0, 1)) }}
                        </div>


                        <div>

                            <p
                                class="font-semibold
                                       text-slate-900"
                            >
                                {{ $vehicle->currentAssignment->driver->name }}
                            </p>

                            <p
                                class="mt-1 text-sm
                                       text-slate-500"
                            >
                                {{ $vehicle->currentAssignment->driver->phone ?: 'No phone number' }}
                            </p>

                        </div>

                    </div>

                @else

                    <div
                        class="rounded-xl
                               border border-slate-200
                               bg-slate-50
                               px-4 py-5"
                    >

                        <p
                            class="text-sm font-medium
                                   text-slate-700"
                        >
                            No driver assigned
                        </p>

                        <p
                            class="mt-1 text-sm
                                   text-slate-500"
                        >
                            This vehicle is currently not assigned to a driver.
                        </p>

                    </div>

                @endif

            </div>

        </div>


        {{-- Location --}}
        <div
            class="rounded-2xl
                   border border-slate-200
                   bg-white"
        >

            <div
                class="border-b border-slate-200
                       px-6 py-5"
            >

                <h2 class="text-base font-semibold text-slate-900">
                    Last Known Location
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Latest location recorded for this vehicle.
                </p>

            </div>


            <div class="p-6">

                @if ($vehicle->last_location)

                    <div
                        id="vehicle-map"
                        class="h-64 w-full rounded-lg"
                    ></div>

                    <div
                        class="vehicle-location-info mt-2 text-xs text-blue-600"
                    >
                        Live tracking via WebSocket
                    </div>

                @else

                    <div
                        class="rounded-xl
                               border border-slate-200
                               bg-slate-50
                               px-4 py-5"
                    >

                        <p
                            class="text-sm font-medium
                                   text-slate-700"
                        >
                            No location available
                        </p>

                        <p
                            class="mt-1 text-sm
                                   text-slate-500"
                        >
                            This vehicle has not reported a location yet.
                        </p>

                    </div>

                @endif

            </div>

        </div>

    </div>

     {{-- ============================================================ --}}
{{-- Location History --}}
{{-- ============================================================ --}}

<div class="rounded-2xl border border-slate-200 bg-white">

    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="text-lg font-semibold text-slate-900">
            Location History
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Recent location updates reported by this vehicle.
        </p>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full">

            <thead class="bg-slate-50">

                <tr class="text-left text-xs font-semibold
                           uppercase tracking-wider text-slate-500">

                    <th class="px-6 py-4">
                        Recorded At
                    </th>

                    <th class="px-6 py-4">
                        Location
                    </th>

                    <th class="px-6 py-4">
                        Speed
                    </th>

                    <th class="px-6 py-4">
                        Heading
                    </th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-100">

                @forelse ($this->locationHistory as $log)

                    <tr class="transition hover:bg-slate-50">

                        <td class="whitespace-nowrap px-6 py-4">

                            <span class="text-sm text-slate-700">
                                {{ $log->recorded_at->format('M j, Y H:i:s') }}
                            </span>

                        </td>

                        <td class="whitespace-nowrap px-6 py-4">

                            @if ($log->location)

                                <span class="text-sm text-slate-700">
                                    {{ number_format($log->location->getLatitude(), 6) }},
                                    {{ number_format($log->location->getLongitude(), 6) }}
                                </span>

                            @else

                                <span class="text-slate-400">
                                    —
                                </span>

                            @endif

                        </td>

                        <td class="whitespace-nowrap px-6 py-4">

                            @if ($log->speed !== null)

                                <span class="text-sm text-slate-700">
                                    {{ number_format((float) $log->speed, 2) }}
                                </span>

                            @else

                                <span class="text-slate-400">
                                    —
                                </span>

                            @endif

                        </td>

                        <td class="whitespace-nowrap px-6 py-4">

                            @if ($log->heading !== null)

                                <span class="text-sm text-slate-700">
                                    {{ $log->heading }}°
                                </span>

                            @else

                                <span class="text-slate-400">
                                    —
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" class="px-6 py-10 text-center">

                            <p class="text-sm font-medium text-slate-700">
                                No location history yet.
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Location updates will appear here once this vehicle reports its position.
                            </p>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    @if ($this->locationHistory->hasPages())

        <div class="border-t border-slate-200 px-6 py-4">

            {{ $this->locationHistory->links() }}

        </div>

    @endif

</div>
    {{-- ============================================================= --}}
    {{-- Active Shipments --}}
    {{-- ============================================================= --}}

    <div
        class="overflow-hidden
               rounded-2xl
               border border-slate-200
               bg-white"
    >

        <div
            class="border-b border-slate-200
                   px-6 py-5"
        >

            <h2 class="text-base font-semibold text-slate-900">
                Active Shipments
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                The latest shipments currently assigned to this vehicle.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">

                    <tr>

                        <th
                            class="px-6 py-4 text-left
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Tracking Number
                        </th>

                        <th
                            class="px-6 py-4 text-left
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Status
                        </th>

                        <th
                            class="px-6 py-4 text-left
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Estimated Arrival
                        </th>

                        <th
                            class="px-6 py-4 text-right
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($vehicle->shipments as $shipment)

                        <tr
                            wire:key="vehicle-shipment-{{ $shipment->id }}"
                            class="transition hover:bg-slate-50"
                        >

                            {{-- Tracking --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4"
                            >

                                <span
                                    class="font-semibold
                                           text-slate-900"
                                >
                                    {{ $shipment->tracking_number }}
                                </span>

                            </td>


                            {{-- Status --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4"
                            >

                                @if ($shipment->status === 'assigned')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-blue-100
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-blue-700"
                                    >
                                        Assigned
                                    </span>

                                @elseif ($shipment->status === 'picked_up')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-amber-100
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-amber-700"
                                    >
                                        Picked Up
                                    </span>

                                @else

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-slate-100
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-slate-700"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                    </span>

                                @endif

                            </td>


                            {{-- ETA --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4 text-sm
                                       text-slate-600"
                            >

                                {{ $shipment->estimated_arrival?->format('M d, Y H:i') ?: '—' }}

                            </td>


                            {{-- Amount --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4 text-right
                                       text-sm font-semibold
                                       text-slate-900"
                            >

                                @if ($shipment->total_amount !== null)

                                    ${{ number_format((float) $shipment->total_amount, 2) }}

                                @else

                                    —

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="px-6 py-12
                                       text-center"
                            >

                                <div class="mx-auto max-w-sm">

                                    <div class="text-4xl">
                                        📦
                                    </div>

                                    <h3
                                        class="mt-3
                                               font-semibold
                                               text-slate-900"
                                    >
                                        No active shipments
                                    </h3>

                                    <p
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        This vehicle currently has no
                                        assigned or picked-up shipments.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- Footer --}}
    {{-- ============================================================= --}}

    <div class="flex justify-start">

        <a
            href="{{ route('vehicles.index') }}"
            wire:navigate
            class="inline-flex items-center
                   justify-center
                   rounded-xl
                   border border-slate-300
                   px-5 py-3
                   text-sm font-semibold
                   text-slate-700
                   transition
                   hover:bg-slate-50"
        >
            ← Back to Vehicles
        </a>

    </div>

</div>


@script

<script>

    const vehicleId = @json($vehicle->id);

    function initializeVehicleMap() {

        const mapElement = document.getElementById('vehicle-map');

        if (!mapElement) {
            return;
        }

        if (mapElement._leaflet_id) {
            return;
        }

        const vehicleLat = @json($vehicle->last_location ? $vehicle->last_location->getLatitude() : null);
        const vehicleLng = @json($vehicle->last_location ? $vehicle->last_location->getLongitude() : null);

        if (vehicleLat === null || vehicleLng === null) {
            return;
        }

        const map = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }
        ).addTo(map);

        const vehicleIcon = L.divIcon({
            className: 'vehicle-marker',
            html: `
                <div
                    style="
                        width:42px;
                        height:42px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:50%;
                        background:#2563eb;
                        border:4px solid white;
                        box-shadow:0 4px 12px rgba(0,0,0,.25);
                        font-size:20px;
                    "
                >
                    🚚
                </div>
            `,
            iconSize: [42, 42],
            iconAnchor: [21, 21],
        });

        const vehicleMarker = L.marker(
            [vehicleLat, vehicleLng],
            {
                icon: vehicleIcon,
                title: 'Current Vehicle Location',
            }
        )
        .addTo(map)
        .bindPopup(`
            <div style="min-width:160px">
                <strong>🚚 Vehicle</strong>
                <br>
                <span style="color:#64748b">
                    Current location
                </span>
            </div>
        `);

        map.setView([vehicleLat, vehicleLng], 14);

        window.__vehicleMap = map;
        window.__vehicleMarker = vehicleMarker;

    }

    document.addEventListener('livewire:navigating', () => {
    if (window.__vehicleLocationSubscription) {
        window.__vehicleLocationSubscription.leave();
        window.__vehicleLocationSubscription = null;
    }

    if (window.__vehicleMap) {
        window.__vehicleMap.remove();
        window.__vehicleMap = null;
        window.__vehicleMarker = null;
    }
});

    document.addEventListener('livewire:navigated', initializeVehicleMap);

    initializeVehicleMap();

    if (vehicleId && window.subscribeToVehicleLocation) {
        window.__vehicleLocationSubscription = subscribeToVehicleLocation(vehicleId, {
            onUpdate: (data) => {
                if (data.location) {
                    const lat = data.location.coordinates[1];
                    const lng = data.location.coordinates[0];

                    if (window.__vehicleMarker && window.__vehicleMap) {
                        window.__vehicleMarker.setLatLng([lat, lng]);
                        window.__vehicleMap.panTo([lat, lng]);
                    }

                    const locationInfo = document.querySelector('.vehicle-location-info');
                    if (locationInfo) {
                        locationInfo.textContent = `Live updated · ${new Date().toLocaleTimeString()}`;
                    }
                }
            },
        });
    }

</script>

@endscript
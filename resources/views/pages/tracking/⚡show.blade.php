<?php

use App\Models\Shipment;
use Livewire\Component;

new class extends Component
{
    public string $trackingNumber = '';

    public ?Shipment $shipment = null;

    public function mount(string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;

        $this->shipment = Shipment::query()
            ->where('tracking_number', $trackingNumber)
            ->with([
                'items:id,shipment_id,description,quantity',
                'statusHistory:id,shipment_id,status,created_at',
                'vehicle:id,status,last_location',
            ])
            ->first();
    }

    public function getCurrentVehicleLatitudeProperty(): ?float
    {
        if (
            ! $this->shipment ||
            ! in_array($this->shipment->status, ['assigned', 'picked_up', 'in_transit'])
        ) {
            return null;
        }

        $location = $this->shipment->vehicle?->last_location;

        return $location
            ? (float) $location->getLatitude()
            : null;
    }

    public function getCurrentVehicleLongitudeProperty(): ?float
    {
        if (
            ! $this->shipment ||
            ! in_array($this->shipment->status, ['assigned', 'picked_up', 'in_transit'])
        ) {
            return null;
        }

        $location = $this->shipment->vehicle?->last_location;

        return $location
            ? (float) $location->getLongitude()
            : null;
    }

    public function getStatusLabelProperty(): string
    {
        if (! $this->shipment) {
            return 'Not Found';
        }

        return match ($this->shipment->status) {
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => str($this->shipment->status)
                ->replace('_', ' ')
                ->title(),
        };
    }

    public function getStatusClassesProperty(): string
    {
        if (! $this->shipment) {
            return 'bg-slate-100 text-slate-700';
        }

        return match ($this->shipment->status) {
            'pending' => 'bg-amber-100 text-amber-700',
            'assigned' => 'bg-blue-100 text-blue-700',
            'picked_up' => 'bg-indigo-100 text-indigo-700',
            'in_transit' => 'bg-blue-100 text-blue-700',
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
};
?>

<div class="min-h-screen bg-slate-50">

    {{-- ============================================================= --}}
    {{-- Public Header --}}
    {{-- ============================================================= --}}

    <header class="border-b border-slate-200 bg-white">

        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">

            <div>

                <div class="text-xl font-bold text-slate-900">
                    GeoLogistics
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Shipment Tracking
                </div>

            </div>

            <div
                class="rounded-full border border-slate-200
                       bg-slate-50 px-4 py-2
                       text-xs font-medium text-slate-600"
            >
                Public Tracking
            </div>

        </div>

    </header>


    {{-- ============================================================= --}}
    {{-- Main --}}
    {{-- ============================================================= --}}

    <main class="mx-auto max-w-6xl px-6 py-10">

        {{-- ========================================================= --}}
        {{-- Shipment Not Found --}}
        {{-- ========================================================= --}}

        @if (! $shipment)

            <div class="mx-auto max-w-xl">

                <div
                    class="rounded-2xl border
                           border-red-200
                           bg-white p-8 text-center
                           shadow-sm"
                >

                    <div
                        class="mx-auto flex h-16 w-16
                               items-center justify-center
                               rounded-full bg-red-50
                               text-3xl"
                    >
                        !
                    </div>

                    <h1
                        class="mt-5 text-xl
                               font-bold text-slate-900"
                    >
                        Tracking Number Not Found
                    </h1>

                    <p
                        class="mx-auto mt-2 max-w-md
                               text-sm leading-6
                               text-slate-500"
                    >
                        We couldn't find a shipment with the tracking
                        number you provided.
                    </p>

                    <div
                        class="mt-6 rounded-xl
                               bg-slate-50 px-4 py-3"
                    >

                        <div
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400"
                        >
                            Tracking Number
                        </div>

                        <div
                            class="mt-1 break-all
                                   font-mono text-sm
                                   font-semibold text-slate-800"
                        >
                            {{ $trackingNumber }}
                        </div>

                    </div>

                </div>

            </div>


        @else


            {{-- ===================================================== --}}
            {{-- Page Heading --}}
            {{-- ===================================================== --}}

            <div class="mb-8">

                <p
                    class="text-sm font-medium
                           text-blue-600"
                >
                    Shipment Tracking
                </p>

                <h1
                    class="mt-1 text-3xl
                           font-bold text-slate-900"
                >
                    Track your shipment
                </h1>

                <div
                    class="mt-3 flex flex-wrap
                           items-center gap-3"
                >

                    <span
                        class="font-mono text-sm
                               font-semibold text-slate-600"
                    >
                        {{ $shipment->tracking_number }}
                    </span>

                    <span
                        class="inline-flex rounded-full
                               px-3 py-1 text-xs
                               font-semibold
                               {{ $this->statusClasses }}"
                    >
                        {{ $this->statusLabel }}
                    </span>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Status Banner --}}
            {{-- ===================================================== --}}

            <div
                class="mb-6 rounded-2xl border
                       border-blue-200
                       bg-blue-50 p-5"
            >

                <div
                    class="flex flex-col gap-4
                           sm:flex-row
                           sm:items-center
                           sm:justify-between"
                >

                    <div>

                        <p
                            class="text-xs font-semibold
                                   uppercase tracking-wide
                                   text-blue-600"
                        >
                            Current Status
                        </p>

                        <p
                            class="mt-1 text-xl
                                   font-bold text-blue-900"
                        >
                            {{ $this->statusLabel }}
                        </p>

                    </div>


                    @if ($shipment->estimated_arrival)

                        <div class="sm:text-right">

                            <p
                                class="text-xs font-semibold
                                       uppercase tracking-wide
                                       text-blue-600"
                            >
                                Estimated Arrival
                            </p>

                            <p
                                class="mt-1 text-sm
                                       font-semibold text-blue-900"
                            >
                                {{ $shipment->estimated_arrival->format('M d, Y · h:i A') }}
                            </p>

                        </div>

                    @endif

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Locations --}}
            {{-- ===================================================== --}}

            <div
                class="grid grid-cols-1 gap-6
                       lg:grid-cols-2"
            >

                {{-- Pickup --}}
                <div
                    class="rounded-2xl border
                           border-slate-200
                           bg-white p-6 shadow-sm"
                >

                    <div class="flex items-start gap-4">

                        <div
                            class="flex h-11 w-11
                                   shrink-0 items-center
                                   justify-center
                                   rounded-xl bg-emerald-50
                                   text-lg text-emerald-600"
                        >
                            A
                        </div>

                        <div class="min-w-0">

                            <p
                                class="text-xs font-semibold
                                       uppercase tracking-wide
                                       text-slate-400"
                            >
                                Pickup Location
                            </p>

                            <p
                                class="mt-2 text-sm
                                       font-semibold text-slate-900"
                            >
                                Shipment Origin
                            </p>

                            @if ($shipment->pickup_location)

                                <div
                                    class="mt-2 rounded-lg
                                           bg-slate-50 px-3 py-2"
                                >

                                    <p
                                        class="font-mono text-xs
                                               text-slate-600"
                                    >
                                        Lat:
                                        {{ number_format($shipment->pickup_location->getLatitude(), 6) }}
                                    </p>

                                    <p
                                        class="mt-1 font-mono
                                               text-xs text-slate-600"
                                    >
                                        Lng:
                                        {{ number_format($shipment->pickup_location->getLongitude(), 6) }}
                                    </p>

                                </div>

                            @else

                                <p
                                    class="mt-2 text-sm
                                           text-slate-400"
                                >
                                    Location not available.
                                </p>

                            @endif

                        </div>

                    </div>

                </div>


                {{-- Dropoff --}}
                <div
                    class="rounded-2xl border
                           border-slate-200
                           bg-white p-6 shadow-sm"
                >

                    <div class="flex items-start gap-4">

                        <div
                            class="flex h-11 w-11
                                   shrink-0 items-center
                                   justify-center
                                   rounded-xl bg-red-50
                                   text-lg text-red-600"
                        >
                            B
                        </div>

                        <div class="min-w-0">

                            <p
                                class="text-xs font-semibold
                                       uppercase tracking-wide
                                       text-slate-400"
                            >
                                Drop-off Location
                            </p>

                            <p
                                class="mt-2 text-sm
                                       font-semibold text-slate-900"
                            >
                                Shipment Destination
                            </p>

                            @if ($shipment->dropoff_location)

                                <div
                                    class="mt-2 rounded-lg
                                           bg-slate-50 px-3 py-2"
                                >

                                    <p
                                        class="font-mono text-xs
                                               text-slate-600"
                                    >
                                        Lat:
                                        {{ number_format($shipment->dropoff_location->getLatitude(), 6) }}
                                    </p>

                                    <p
                                        class="mt-1 font-mono
                                               text-xs text-slate-600"
                                    >
                                        Lng:
                                        {{ number_format($shipment->dropoff_location->getLongitude(), 6) }}
                                    </p>

                                </div>

                            @else

                                <p
                                    class="mt-2 text-sm
                                           text-slate-400"
                                >
                                    Location not available.
                                </p>

                            @endif

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Live Vehicle Location --}}
            {{-- ===================================================== --}}

            @if (
                $this->currentVehicleLatitude !== null &&
                $this->currentVehicleLongitude !== null
            )

                <div
                    class="mt-6 overflow-hidden
                           rounded-2xl border
                           border-slate-200
                           bg-white shadow-sm"
                >

                    {{-- Map Header --}}
                    <div
                        class="flex flex-col gap-4
                               border-b border-slate-200
                               p-6 sm:flex-row
                               sm:items-center
                               sm:justify-between"
                    >

                        <div class="flex items-center gap-3">

                            <div
                                class="flex h-11 w-11
                                       items-center justify-center
                                       rounded-xl bg-blue-50
                                       text-xl"
                            >
                                🚚
                            </div>

                            <div>

                                <div class="flex items-center gap-2">

                                    <h2
                                        class="text-base
                                               font-semibold
                                               text-slate-900"
                                    >
                                        Live Vehicle Location
                                    </h2>

                                    <span
                                        class="inline-flex
                                               items-center gap-1.5
                                               rounded-full
                                               bg-emerald-50
                                               px-2.5 py-1
                                               text-[11px]
                                               font-semibold
                                               text-emerald-700"
                                    >
                                        <span
                                            class="h-1.5 w-1.5
                                                   rounded-full
                                                   bg-emerald-500"
                                        ></span>

                                        LIVE
                                    </span>

                                </div>

                                <p
                                    class="mt-1 text-sm
                                           text-slate-500"
                                >
                                    Current vehicle position
                                </p>

                            </div>

                        </div>


                        <div
                            class="rounded-xl
                                   bg-slate-50 px-4 py-3"
                        >

                            <p
                                class="font-mono text-xs
                                       text-slate-600"
                            >
                                {{ number_format($this->currentVehicleLatitude, 6) }},
                                {{ number_format($this->currentVehicleLongitude, 6) }}
                            </p>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- Map --}}
                    {{-- ================================================= --}}

                    <div
                        wire:ignore
                        id="shipment-map"
                        class="h-[420px] w-full"
                    ></div>


                    {{-- Map Footer --}}
                    <div
                        class="flex flex-wrap
                               items-center gap-5
                               border-t border-slate-200
                               bg-slate-50 px-6 py-4"
                    >

                        <div
                            class="flex items-center gap-2
                                   text-xs text-slate-600"
                        >

                            <span
                                class="h-3 w-3 rounded-full
                                       bg-emerald-500"
                            ></span>

                            Pickup
                        </div>


                        <div
                            class="flex items-center gap-2
                                   text-xs text-slate-600"
                        >

                            <span
                                class="h-3 w-3 rounded-full
                                       bg-red-500"
                            ></span>

                            Drop-off
                        </div>


                        <div
                            class="flex items-center gap-2
                                   text-xs text-slate-600"
                        >

                            <span
                                class="h-3 w-3 rounded-full
                                       bg-blue-600"
                            ></span>

                            Vehicle
                        </div>

                    </div>

                </div>

            @endif


            {{-- ===================================================== --}}
            {{-- Shipment Items --}}
            {{-- ===================================================== --}}

            <div
                class="mt-6 rounded-2xl border
                       border-slate-200
                       bg-white shadow-sm"
            >

                <div
                    class="border-b border-slate-200
                           px-6 py-5"
                >

                    <h2
                        class="font-semibold
                               text-slate-900"
                    >
                        Shipment Items
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-slate-500"
                    >
                        Items included in this shipment.
                    </p>

                </div>


                <div class="divide-y divide-slate-100">

                    @forelse ($shipment->items as $item)

                        <div
                            class="flex items-center
                                   justify-between gap-4
                                   px-6 py-4"
                        >

                            <div class="min-w-0">

                                <p
                                    class="truncate text-sm
                                           font-medium
                                           text-slate-900"
                                >
                                    {{ $item->description }}
                                </p>

                            </div>

                            <div
                                class="shrink-0 rounded-lg
                                       bg-slate-100 px-3 py-1
                                       text-xs font-semibold
                                       text-slate-700"
                            >
                                × {{ $item->quantity }}
                            </div>

                        </div>

                    @empty

                        <div
                            class="px-6 py-8
                                   text-center text-sm
                                   text-slate-500"
                        >
                            No shipment items available.
                        </div>

                    @endforelse

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Status Timeline --}}
            {{-- ===================================================== --}}

            <div
                class="mt-6 rounded-2xl border
                       border-slate-200
                       bg-white p-6 shadow-sm"
            >

                <div class="mb-6">

                    <h2
                        class="font-semibold
                               text-slate-900"
                    >
                        Shipment History
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-slate-500"
                    >
                        Track the progress of your shipment.
                    </p>

                </div>


                <div class="space-y-6">

                    @forelse ($shipment->statusHistory->sortByDesc('created_at') as $history)

                        <div class="flex gap-4">

                            <div
                                class="flex shrink-0
                                       flex-col items-center"
                            >

                                <div
                                    class="flex h-9 w-9
                                           items-center
                                           justify-center
                                           rounded-full
                                           bg-blue-100
                                           text-xs font-bold
                                           text-blue-700"
                                >
                                    ✓
                                </div>

                                @if (! $loop->last)

                                    <div
                                        class="mt-2 h-full
                                               w-px bg-slate-200"
                                    ></div>

                                @endif

                            </div>


                            <div
                                class="min-w-0 pb-2"
                            >

                                <p
                                    class="text-sm font-semibold
                                           text-slate-900"
                                >
                                    {{ str($history->status)->replace('_', ' ')->title() }}
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-slate-500"
                                >
                                    {{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y · h:i A') }}
                                </p>

                            </div>

                        </div>

                    @empty

                        <p
                            class="text-sm
                                   text-slate-500"
                        >
                            No shipment history available.
                        </p>

                    @endforelse

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- Footer --}}
            {{-- ===================================================== --}}

            <div
                class="mt-8 text-center text-xs
                       text-slate-400"
            >
                GeoLogistics · Public Shipment Tracking
            </div>

        @endif

    </main>

</div>


{{-- ================================================================ --}}
{{-- Leaflet --}}
{{-- ================================================================ --}}

@script

<script>

    const shipmentMapData = {
        vehicle: {
            lat: @json($this->currentVehicleLatitude),
            lng: @json($this->currentVehicleLongitude),
        },

        pickup: {
            lat: @json(
                $shipment?->pickup_location
                    ? $shipment->pickup_location->getLatitude()
                    : null
            ),
            lng: @json(
                $shipment?->pickup_location
                    ? $shipment->pickup_location->getLongitude()
                    : null
            ),
        },

        dropoff: {
            lat: @json(
                $shipment?->dropoff_location
                    ? $shipment->dropoff_location->getLatitude()
                    : null
            ),
            lng: @json(
                $shipment?->dropoff_location
                    ? $shipment->dropoff_location->getLongitude()
                    : null
            ),
        },
    };


    function initializeShipmentMap() {

        const mapElement = document.getElementById('shipment-map');

        if (! mapElement) {
            return;
        }

        if (
            shipmentMapData.vehicle.lat === null ||
            shipmentMapData.vehicle.lng === null
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate map initialization
        |--------------------------------------------------------------------------
        */

        if (mapElement._leaflet_id) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Create Map
        |--------------------------------------------------------------------------
        */

        const vehiclePosition = [
            shipmentMapData.vehicle.lat,
            shipmentMapData.vehicle.lng
        ];

        const map = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true,
        });


        /*
        |--------------------------------------------------------------------------
        | OpenStreetMap Tiles
        |--------------------------------------------------------------------------
        */

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);


        /*
        |--------------------------------------------------------------------------
        | Custom Vehicle Icon
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Vehicle Marker
        |--------------------------------------------------------------------------
        */

        const vehicleMarker = L.marker(
            vehiclePosition,
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


        /*
        |--------------------------------------------------------------------------
        | Pickup Marker
        |--------------------------------------------------------------------------
        */

        let pickupMarker = null;

        if (
            shipmentMapData.pickup.lat !== null &&
            shipmentMapData.pickup.lng !== null
        ) {

            pickupMarker = L.circleMarker(
                [
                    shipmentMapData.pickup.lat,
                    shipmentMapData.pickup.lng
                ],
                {
                    radius: 9,
                    weight: 3,
                    color: '#ffffff',
                    fillColor: '#10b981',
                    fillOpacity: 1,
                }
            )
            .addTo(map)
            .bindPopup(`
                <strong>Pickup Location</strong>
            `);

        }


        /*
        |--------------------------------------------------------------------------
        | Dropoff Marker
        |--------------------------------------------------------------------------
        */

        let dropoffMarker = null;

        if (
            shipmentMapData.dropoff.lat !== null &&
            shipmentMapData.dropoff.lng !== null
        ) {

            dropoffMarker = L.circleMarker(
                [
                    shipmentMapData.dropoff.lat,
                    shipmentMapData.dropoff.lng
                ],
                {
                    radius: 9,
                    weight: 3,
                    color: '#ffffff',
                    fillColor: '#ef4444',
                    fillOpacity: 1,
                }
            )
            .addTo(map)
            .bindPopup(`
                <strong>Drop-off Location</strong>
            `);

        }


        /*
        |--------------------------------------------------------------------------
        | Route Line
        |--------------------------------------------------------------------------
        */

        const routePoints = [];

        if (pickupMarker) {
            routePoints.push([
                shipmentMapData.pickup.lat,
                shipmentMapData.pickup.lng
            ]);
        }

        routePoints.push(vehiclePosition);

        if (dropoffMarker) {
            routePoints.push([
                shipmentMapData.dropoff.lat,
                shipmentMapData.dropoff.lng
            ]);
        }


        if (routePoints.length >= 2) {

            L.polyline(
                routePoints,
                {
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.65,
                    dashArray: '8, 8',
                }
            ).addTo(map);

        }


        /*
        |--------------------------------------------------------------------------
        | Fit Map To All Points
        |--------------------------------------------------------------------------
        */

        const bounds = [];

        bounds.push(vehiclePosition);

        if (pickupMarker) {
            bounds.push([
                shipmentMapData.pickup.lat,
                shipmentMapData.pickup.lng
            ]);
        }

        if (dropoffMarker) {
            bounds.push([
                shipmentMapData.dropoff.lat,
                shipmentMapData.dropoff.lng
            ]);
        }


        if (bounds.length > 1) {

            map.fitBounds(
                bounds,
                {
                    padding: [50, 50],
                    maxZoom: 14,
                }
            );

        } else {

            map.setView(
                vehiclePosition,
                14
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Save Map References
        |--------------------------------------------------------------------------
        */

        window.shipmentMap = map;
        window.shipmentVehicleMarker = vehicleMarker;

    }


    /*
    |--------------------------------------------------------------------------
    | Initialize
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'livewire:navigated',
        initializeShipmentMap
    );

    initializeShipmentMap();

</script>

@endscript
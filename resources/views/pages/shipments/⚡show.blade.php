<?php

use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use Livewire\Component;
use App\Services\DispatchService;
use App\Services\RoutingService;
use App\Models\ShipmentProof;
use Livewire\WithFileUploads;


new class extends Component
{
     use WithFileUploads;

    public Shipment $shipment;
   

    public string $selected_vehicle_id = '';
    public ?array $suggested_vehicle = null;
    public string $status_note = '';
    public int $rating = 0;
    public string $rating_comment = '';
    public string $recipient_name = '';
    public $proof_photo = null;
    public string $proof_signature = '';
    public ?float $proof_latitude = null;
    public ?float $proof_longitude = null;
    public bool $showProofForm = false;
  



public string $delivery_latitude = '';

public string $delivery_longitude = '';

   public string $signature = '';

   public $photo = null;


 public function mount(Shipment $shipment): void
{
    $this->authorize('view', $shipment);

    $this->shipment = $shipment->load([
        'customer:id,name,phone,email',
        'vehicle.currentAssignment.driver:id,name,phone',
        'items',
        'statusHistory.changedBy',
        'payment',
        'rating',
        'proof',
    ]);

    $this->selected_vehicle_id = (string) ($shipment->vehicle_id ?? '');
}

    public function submitRating(): void
    {
        $user = auth()->user();

        if ($user->role !== 'customer' || $this->shipment->customer_id !== $user->id) {
            abort(403);
        }

        if ($this->shipment->status !== 'delivered') {
            session()->flash('error', 'You can only rate a shipment after it has been delivered.');
            return;
        }

        if ($this->shipment->rating) {
            session()->flash('error', 'This shipment has already been rated.');
            return;
        }

        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'rating_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->shipment->rating()->create([
            'rated_by' => $user->id,
            'score' => $this->rating,
            'comment' => $this->rating_comment ?: null,
        ]);

        $this->shipment->load('rating');

        $this->rating = 0;
        $this->rating_comment = '';

        session()->flash('success', 'Rating submitted successfully.');
    }

    public function getVehiclesProperty()
    {
        return Vehicle::query()->orderBy('plate_number')->get();
    }
     public function suggestVehicle(): void
{
    $this->authorize('update', $this->shipment);

    $this->suggested_vehicle = app(DispatchService::class)
        ->suggestVehicle($this->shipment);

    if (! $this->suggested_vehicle) {
        session()->flash(
            'error',
            'No suitable vehicle is currently available.'
        );

        return;
    }

    session()->flash(
        'success',
        'Vehicle suggestion found successfully.'
    );
}

   public function openProofForm(): void
{
    $this->authorize('update', $this->shipment);

    if ($this->shipment->status !== 'delivered') {
        session()->flash(
            'error',
            'Proof can only be added after delivery.'
        );

        return;
    }

    $this->showProofForm = true;
}
   public function saveProof(): void
{
        $this->authorize('update', $this->shipment);
        $user = auth()->user();

    if ($user->role !== 'driver') {
        abort(403);
    }

     $isAssignedDriver = $user->vehicleAssignments()
        ->where('is_active', true)
        ->where('vehicle_id', $this->shipment->vehicle_id)
        ->exists();

    if (! $isAssignedDriver) {
        abort(403);
    }

    if ($this->shipment->status !== 'delivered') {
        session()->flash(
            'error',
            'Proof can only be added after delivery.'
        );

        return;
    }


    $validated = $this->validate([
        'recipient_name' => [
            'required',
            'string',
            'max:255',
        ],

        'photo' => [
            'nullable',
            'image',
            'max:2048',
        ],

        'signature' => [
            'nullable',
            'string',
        ],

        'delivery_latitude' => [
            'nullable',
            'numeric',
            'between:-90,90',
        ],

        'delivery_longitude' => [
            'nullable',
            'numeric',
            'between:-180,180',
        ],
    ]);


    $photoPath = null;


    if ($this->photo) {

        $photoPath = $this->photo
            ->store('delivery-proofs', 'public');

    }


    $this->shipment->proof()->create([

        'recorded_by' => auth()->id(),

        'recipient_name' =>
            $validated['recipient_name'],

        'photo_path' =>
            $photoPath,

        'signature' =>
            $validated['signature'] ?: null,

        'latitude' =>
            $validated['delivery_latitude'] ?: null,

        'longitude' =>
            $validated['delivery_longitude'] ?: null,

        'delivered_at' =>
            now(),

    ]);


    $this->shipment->load('proof');

    $this->showProofForm = false;
    $this->reset([
        'recipient_name',
        'photo',
        'signature',
        'delivery_latitude',
        'delivery_longitude',
    ]);


    session()->flash(
        'success',
        'Delivery proof saved successfully.'
    );
}
    public function assignVehicle(): void
    {
        $this->authorize('update', $this->shipment);

        $this->validate([
            'selected_vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
        ]);

        $this->shipment->update([
            'vehicle_id' => $this->selected_vehicle_id,
            'status' => 'assigned',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $this->shipment->id,
            'status' => 'assigned',
            'changed_by' => auth()->id(),
            'note' => 'Vehicle assigned to shipment',
        ]);

        AuditLogger::log(
            'shipment.assigned',
            'Shipment',
            $this->shipment->id,
            'Vehicle assigned'
        );

        $this->shipment->refresh();

        session()->flash('success', 'Vehicle assigned successfully.');
    }

    public function updateStatus(string $status): void
    {
        $this->authorize('update', $this->shipment);

        $user = auth()->user();

        $allowedTransitions = [
            'pending' => ['assigned', 'cancelled'],
            'assigned' => ['picked_up', 'cancelled'],
            'picked_up' => ['in_transit'],
            'in_transit' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        if ($user->role === 'driver') {
            $driverTransitions = [
                'assigned' => ['picked_up'],
                'picked_up' => ['in_transit'],
                'in_transit' => ['delivered'],
            ];

            $allowedTransitions = $driverTransitions;
        }

        if (! in_array($status, $allowedTransitions[$this->shipment->status] ?? [])) {
            session()->flash('error', 'Invalid status transition.');
            return;
        }

        $oldStatus = $this->shipment->status;

        $this->shipment->update([
            'status' => $status,
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $this->shipment->id,
            'status' => $status,
            'changed_by' => $user->id,
            'note' => "Status changed from {$oldStatus} to {$status}",
        ]);

        AuditLogger::log(
            'shipment.status_changed',
            'Shipment',
            $this->shipment->id,
            "Status changed to {$status}"
        );

        $this->shipment->refresh();

        session()->flash('success', 'Shipment status updated successfully.');
    }
     
    public function addNote(): void
    {
        $this->authorize('update', $this->shipment);

        $this->validate([
            'status_note' => ['required', 'string', 'max:500'],
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $this->shipment->id,
            'status' => $this->shipment->status,
            'changed_by' => auth()->id(),
            'note' => $this->status_note,
        ]);

        AuditLogger::log(
            'shipment.note_added',
            'Shipment',
            $this->shipment->id,
            $this->status_note
        );

        $this->status_note = '';

        $this->shipment->refresh();

        session()->flash('success', 'Status note added successfully.');
    }

    public function cancelShipment(): void
    {
        $this->authorize('update', $this->shipment);

        if (! in_array($this->shipment->status, ['pending', 'assigned'])) {
            session()->flash('error', 'Only pending or assigned shipments can be cancelled.');
            return;
        }

        $this->shipment->update([
            'status' => 'cancelled',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $this->shipment->id,
            'status' => 'cancelled',
            'changed_by' => auth()->id(),
            'note' => 'Shipment cancelled',
        ]);

        AuditLogger::log(
            'shipment.cancelled',
            'Shipment',
            $this->shipment->id,
            'Shipment cancelled'
        );

        $this->shipment->refresh();

        session()->flash('success', 'Shipment cancelled successfully.');
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
        return match ($this->shipment->status) {
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->shipment->status),
        };
    }

    public function getStatusClassesProperty(): string
    {
        return match ($this->shipment->status) {
            'pending' => 'bg-amber-100 text-amber-700',
            'assigned' => 'bg-blue-100 text-blue-700',
            'picked_up' => 'bg-purple-100 text-purple-700',
            'in_transit' => 'bg-blue-100 text-blue-700',
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
};
?>

<style>
    @media print {
        @page {
            size: A4;
            margin: 14mm;
        }

        body {
            background: white !important;
        }

        .no-print,
        nav,
        aside,
        header,
        footer,
        [data-sidebar],
        [data-navigation],
        #shipment-map,
        .leaflet-container {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }

        .print-section {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }

        .print-table th {
            background: #f3f4f6 !important;
        }

        .print-card {
            border: 1px solid #d1d5db !important;
            box-shadow: none !important;
        }

        .print-status {
            border: 1px solid #9ca3af;
            padding: 4px 10px;
            border-radius: 9999px;
        }

        .print-page {
            display: block !important;
        }
    }

    .print-only {
        display: none;
    }
</style>

<div class="space-y-6">

    {{-- Print-only Header --}}
    <div class="print-only print-page mb-8">
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    GeoLogistics
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Shipment Document
                </p>
            </div>

            <div class="text-right">
                <p class="text-xs text-slate-500">
                    Tracking Number
                </p>

                <p class="mt-1 text-lg font-bold text-slate-900">
                    {{ $shipment->tracking_number }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Printed: {{ now()->format('Y-m-d H:i') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="no-print flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-center gap-4">

            <a
                href="{{ route('shipments.index') }}"
                wire:navigate
                class="flex h-10 w-10 items-center justify-center rounded-xl
                       border border-slate-200 bg-white text-slate-600
                       hover:bg-slate-50"
            >
                ←
            </a>

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Shipment Details
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $shipment->tracking_number }}
                </p>
            </div>

            @if (auth()->user()->role !== 'customer')
                <a
                    href="{{ route('shipments.edit', $shipment) }}"
                    wire:navigate
                    class="inline-flex items-center justify-center gap-2
                           rounded-xl bg-blue-600
                           px-4 py-2.5
                           text-sm font-semibold text-white
                           transition hover:bg-blue-700"
                >
                    ✏️ Edit Shipment
                </a>
            @endif

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center justify-center gap-2
                       rounded-xl border border-slate-300 bg-white
                       px-4 py-2.5 text-sm font-semibold text-slate-700
                       transition hover:bg-slate-50"
            >
                🖨️ Print Shipment
            </button>

        </div>

        <span
            class="inline-flex w-fit rounded-full px-3 py-1.5 text-sm font-semibold
                   {{ $this->statusClasses }}"
        >
            {{ $this->statusLabel }}
        </span>

    </div>

    {{-- Print Status --}}
    <div class="print-only mb-6">
        <div class="flex items-center justify-between rounded-xl border border-slate-300 px-4 py-3">
            <span class="text-sm font-semibold text-slate-600">
                Shipment Status
            </span>

            <span class="print-status text-sm font-bold text-slate-900">
                {{ $this->statusLabel }}
            </span>
        </div>
    </div>

    {{-- Flash Messages --}}
    <div class="no-print">
        @if (session()->has('success'))
            <div class="rounded-xl bg-emerald-100 p-4 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-xl bg-red-100 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Shipment Information --}}
    <div class="print-section rounded-2xl border border-slate-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-slate-900">
            Shipment Information
        </h2>

        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Tracking Number
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $shipment->tracking_number }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Customer
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $shipment->customer?->name ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Total Amount
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    ${{ number_format((float) $shipment->total_amount, 2) }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Estimated Arrival
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $shipment->estimated_arrival?->format('Y-m-d H:i') ?? '—' }}
                </p>
            </div>

        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 border-t border-slate-100 pt-6 md:grid-cols-3">

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Assigned Vehicle
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $shipment->vehicle ? $shipment->vehicle->plate_number . ' (' . $shipment->vehicle->model . ')' : 'Not Assigned' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Driver
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $shipment->vehicle?->currentAssignment?->driver?->name ?? 'Not Assigned' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Status
                </p>

                <p class="mt-1 font-semibold capitalize text-slate-900">
                    {{ str_replace('_', ' ', $shipment->status) }}
                </p>
            </div>

        </div>

        <div class="print-only mt-6 border-t border-slate-100 pt-6">

            <div class="grid grid-cols-2 gap-5">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Customer Phone
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $shipment->customer?->phone ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Customer Email
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $shipment->customer?->email ?? '—' }}
                    </p>
                </div>

            </div>

        </div>

    </div>

    {{-- Locations --}}
    <div class="print-section grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Pickup --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print-card">

            <div class="flex items-start gap-4">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-600">
                    A
                </div>

                <div class="min-w-0">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Pickup Location
                    </p>

                    @if ($shipment->pickup_location)

                        <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2">

                            <p class="font-mono text-xs text-slate-600">
                                Lat: {{ number_format($shipment->pickup_location->getLatitude(), 6) }}
                            </p>

                            <p class="mt-1 font-mono text-xs text-slate-600">
                                Lng: {{ number_format($shipment->pickup_location->getLongitude(), 6) }}
                            </p>

                        </div>

                    @else

                        <p class="mt-2 text-sm text-slate-400">
                            Location not available.
                        </p>

                    @endif

                </div>

            </div>

        </div>

        {{-- Dropoff --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print-card">

            <div class="flex items-start gap-4">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-lg text-red-600">
                    B
                </div>

                <div class="min-w-0">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Drop-off Location
                    </p>

                    @if ($shipment->dropoff_location)

                        <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2">

                            <p class="font-mono text-xs text-slate-600">
                                Lat: {{ number_format($shipment->dropoff_location->getLatitude(), 6) }}
                            </p>

                            <p class="mt-1 font-mono text-xs text-slate-600">
                                Lng: {{ number_format($shipment->dropoff_location->getLongitude(), 6) }}
                            </p>

                        </div>

                    @else

                        <p class="mt-2 text-sm text-slate-400">
                            Location not available.
                        </p>

                    @endif

                </div>

            </div>

        </div>

    </div>

    {{-- Live Map --}}
    @if ($this->currentVehicleLatitude !== null && $this->currentVehicleLongitude !== null)

        <div class="no-print overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-3">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-xl">
                        🚚
                    </div>

                    <div>

                        <div class="flex items-center gap-2">

                            <h2 class="text-base font-semibold text-slate-900">
                                Live Vehicle Location
                            </h2>

                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                LIVE
                            </span>

                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            Current vehicle position
                        </p>

                    </div>

                </div>

                <div class="rounded-xl bg-slate-50 px-4 py-3">

                    <p class="font-mono text-xs text-slate-600">
                        {{ number_format($this->currentVehicleLatitude, 6) }},
                        {{ number_format($this->currentVehicleLongitude, 6) }}
                    </p>

                </div>

            </div>

            <div wire:ignore id="shipment-map" class="h-[420px] w-full"></div>

        </div>
@php
    $routeToPickup = null;
    $routeToDropoff = null;

    $routingService = app(\App\Services\RoutingService::class);

    if (
        $this->currentVehicleLatitude !== null &&
        $this->currentVehicleLongitude !== null &&
        $shipment?->pickup_location
    ) {
        $route = $routingService->route(
            (float) $this->currentVehicleLatitude,
            (float) $this->currentVehicleLongitude,
            (float) $shipment->pickup_location->getLatitude(),
            (float) $shipment->pickup_location->getLongitude(),
        );

        $routeToPickup = $route['paths'][0]['points']['coordinates'] ?? null;
    }

    if ($shipment?->pickup_location && $shipment?->dropoff_location) {
        $route = $routingService->route(
            (float) $shipment->pickup_location->getLatitude(),
            (float) $shipment->pickup_location->getLongitude(),
            (float) $shipment->dropoff_location->getLatitude(),
            (float) $shipment->dropoff_location->getLongitude(),
        );

        $routeToDropoff = $route['paths'][0]['points']['coordinates'] ?? null;
    }
@endphp
        @script
        <script>
          const shipmentMapData = {
    vehicle: {
        lat: @json($this->currentVehicleLatitude),
        lng: @json($this->currentVehicleLongitude)
    },
    pickup: {
        lat: @json($shipment?->pickup_location?->getLatitude()),
        lng: @json($shipment?->pickup_location?->getLongitude())
    },
   dropoff: {
    lat: @json($shipment?->dropoff_location?->getLatitude()),
    lng: @json($shipment?->dropoff_location?->getLongitude())
},
routeToPickup: @js($routeToPickup),
routeToDropoff: @js($routeToDropoff),
    
};



            function initializeShipmentMap() {
                const mapElement = document.getElementById('shipment-map');

                if (!mapElement || mapElement._leaflet_id) return;

                if (
                    shipmentMapData.vehicle.lat === null ||
                    shipmentMapData.vehicle.lng === null
                ) {
                    return;
                }

                const vehiclePosition = [
                    shipmentMapData.vehicle.lat,
                    shipmentMapData.vehicle.lng
                ];

                const map = L.map(mapElement, {
                    zoomControl: true,
                    scrollWheelZoom: true
                });

                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }
                ).addTo(map);

                const vehicleIcon = L.divIcon({
                    className: 'vehicle-marker',
                    html: '<div style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#2563eb;border:4px solid white;box-shadow:0 4px 12px rgba(0,0,0,.25);font-size:20px;">🚚</div>',
                    iconSize: [42, 42],
                    iconAnchor: [21, 21],
                });

                const vehicleMarker = L.marker(
                    vehiclePosition,
                    {
                        icon: vehicleIcon,
                        title: 'Current Vehicle Location'
                    }
                ).addTo(map);

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
                            fillOpacity: 1
                        }
                    ).addTo(map);
                }

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
                            fillOpacity: 1
                        }
                    ).addTo(map);
                }
               let routeToPickupLine = null;
let routeToDropoffLine = null;

// Vehicle → Pickup
if (
    Array.isArray(shipmentMapData.routeToPickup) &&
    shipmentMapData.routeToPickup.length > 1
) {
    const routeToPickupCoordinates =
        shipmentMapData.routeToPickup.map(
            ([lng, lat]) => [lat, lng]
        );

    routeToPickupLine = L.polyline(
        routeToPickupCoordinates,
        {
            weight: 5,
            opacity: 0.8,
        }
    ).addTo(map);
}

// Pickup → Dropoff
if (
    Array.isArray(shipmentMapData.routeToDropoff) &&
    shipmentMapData.routeToDropoff.length > 1
) {
    const routeToDropoffCoordinates =
        shipmentMapData.routeToDropoff.map(
            ([lng, lat]) => [lat, lng]
        );

    routeToDropoffLine = L.polyline(
        routeToDropoffCoordinates,
        {
            weight: 5,
            opacity: 0.8,
        }
    ).addTo(map);
}
                const bounds = [vehiclePosition];

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
                    map.fitBounds(bounds, {
                        padding: [50, 50],
                        maxZoom: 14
                    });
                } else {
                    map.setView(vehiclePosition, 14);
                }

                window.shipmentMap = map;
                window.shipmentVehicleMarker = vehicleMarker;
            }

            document.addEventListener('livewire:navigating', () => {

                if (window.__vehicleLocationSubscription) {
                    window.__vehicleLocationSubscription.leave();
                    window.__vehicleLocationSubscription = null;
                }

                if (window.shipmentMap) {
                    window.shipmentMap.remove();
                    window.shipmentMap = null;
                    window.shipmentVehicleMarker = null;
                }
            });

            document.addEventListener(
                'livewire:navigated',
                initializeShipmentMap
            );

            initializeShipmentMap();

            const vehicleId = @json($shipment?->vehicle_id);

            if (
                vehicleId &&
                window.subscribeToVehicleLocation
            ) {
                window.__vehicleLocationSubscription =
                    subscribeToVehicleLocation(vehicleId, {
                        onUpdate: (data) => {

                            if (
                                data.location &&
                                window.shipmentVehicleMarker &&
                                window.shipmentMap
                            ) {
                                const lat =
                                    data.location.coordinates[1];

                                const lng =
                                    data.location.coordinates[0];

                                window.shipmentVehicleMarker.setLatLng([
                                    lat,
                                    lng
                                ]);

                                window.shipmentMap.panTo([
                                    lat,
                                    lng
                                ]);
                            }
                        },
                    });
            }
        </script>
        @endscript

    @endif

    @if (auth()->user()->role !== 'customer')

        {{-- Management & Actions Panel --}}
        <div class="no-print rounded-2xl border border-slate-200 bg-white p-6 space-y-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Shipment Management & Actions
            </h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- Assign Vehicle --}}
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-3">

                    <h3 class="font-medium text-slate-900">
                        Assign Vehicle
                    </h3>

                    <div class="flex gap-3">

                        <select
                            wire:model="selected_vehicle_id"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm outline-none"
                        >
                            <option value="">
                                Select vehicle
                            </option>

                            @foreach ($this->vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">
                                    {{ $vehicle->plate_number }} — {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>

                        <button
                            type="button"
                            wire:click="assignVehicle"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                        >
                            Assign
                        </button>

                    </div>
                                        <button
                        type="button"
                        wire:click="suggestVehicle"
                        wire:loading.attr="disabled"
                        class="w-full rounded-xl border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="suggestVehicle">
                            Suggest Vehicle
                        </span>

                        <span wire:loading wire:target="suggestVehicle">
                            Searching...
                        </span>
                    </button>

                    @if ($suggested_vehicle)
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <h4 class="font-semibold text-blue-900">
                                Suggested Vehicle
                            </h4>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs text-slate-500">
                                        Vehicle
                                    </p>
                                    <p class="font-semibold text-slate-900">
                                        {{ $suggested_vehicle['plate_number'] }}
                                    </p>
                                    <p class="text-sm text-slate-500">
                                        {{ $suggested_vehicle['model'] }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">
                                        Driver
                                    </p>
                                    <p class="font-semibold text-slate-900">
                                        {{ $suggested_vehicle['driver_name'] }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">
                                        Distance to Pickup
                                    </p>
                                    <p class="font-semibold text-slate-900">
                                        {{ $suggested_vehicle['distance_km'] }} km
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                wire:click="$set('selected_vehicle_id', '{{ $suggested_vehicle['vehicle_id'] }}')"
                                class="mt-4 rounded-xl border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-100"
                            >
                                Use Suggested Vehicle
                            </button>

                            <p class="mt-2 text-xs text-slate-500">
                                This is only a suggestion. The vehicle will not be assigned automatically.
                            </p>
                        </div>
                    @endif
                    @error('selected_vehicle_id')
                        <p class="text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Status Progression --}}
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-3">

                    <h3 class="font-medium text-slate-900">
                        Status Progression
                    </h3>

                    <div class="flex flex-wrap gap-2">

                        @if ($shipment->status === 'pending')

                            @if (in_array(auth()->user()->role, ['admin', 'dispatcher']))

                                <button
                                    type="button"
                                    wire:click="updateStatus('assigned')"
                                    class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                                >
                                    Set Assigned
                                </button>

                                <button
                                    type="button"
                                    wire:click="cancelShipment"
                                    wire:confirm="Are you sure you want to cancel this shipment?"
                                    class="rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                >
                                    Cancel
                                </button>

                            @else

                                <span class="text-sm italic text-slate-500">
                                    Pending shipment awaiting assignment.
                                </span>

                            @endif

                        @elseif ($shipment->status === 'assigned')

                            <button
                                type="button"
                                wire:click="updateStatus('picked_up')"
                                class="rounded-xl bg-purple-600 px-3 py-2 text-xs font-semibold text-white hover:bg-purple-700"
                            >
                                Start Pickup / Mark as Picked Up
                            </button>

                            @if (in_array(auth()->user()->role, ['admin', 'dispatcher']))

                                <button
                                    type="button"
                                    wire:click="cancelShipment"
                                    wire:confirm="Are you sure you want to cancel this shipment?"
                                    class="rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                >
                                    Cancel
                                </button>

                            @endif

                        @elseif ($shipment->status === 'picked_up')

                            <button
                                type="button"
                                wire:click="updateStatus('in_transit')"
                                class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                            >
                                Start Delivery / Mark as In Transit
                            </button>

                        @elseif ($shipment->status === 'in_transit')

                            <button
                                type="button"
                                wire:click="updateStatus('delivered')"
                                class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                            >
                                Mark as Delivered
                            </button>

                        @else

                            <span class="text-sm italic text-slate-500">
                                No further transitions available
                                ({{ ucfirst(str_replace('_', ' ', $shipment->status)) }})
                            </span>

                        @endif

                    </div>

                </div>

            </div>
            {{-- Proof of Delivery --}}

@if ($shipment->status === 'delivered')

    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 space-y-4">

        <h3 class="font-medium text-emerald-900">
            Proof of Delivery
        </h3>

        @if ($shipment->proof)

            <p class="text-sm text-emerald-700">
                Delivery proof recorded.
            </p>

            {{-- Recipient --}}
            <div>
                <p class="text-xs text-slate-500">
                    Recipient
                </p>

                <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ $shipment->proof->recipient_name }}
                </p>
            </div>

            {{-- Delivery Photo --}}
            @if ($shipment->proof->photo_path)

                <div>
                    <p class="mb-2 text-sm font-medium text-slate-700">
                        Delivery Photo
                    </p>

                    <img
                        src="{{ asset('storage/' . $shipment->proof->photo_path) }}"
                        alt="Delivery proof"
                        class="max-h-80 w-full rounded-xl border border-slate-200 object-contain"
                    >
                </div>

            @endif

            {{-- Recipient Signature --}}
            @if ($shipment->proof->signature)

                <div>
                    <p class="mb-2 text-sm font-medium text-slate-700">
                        Recipient Signature
                    </p>

                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <img
                            src="{{ $shipment->proof->signature }}"
                            alt="Recipient signature"
                            class="max-h-40 w-full object-contain"
                        >
                    </div>
                </div>

            @endif

            {{-- Delivery Information --}}
            <div class="grid gap-3 sm:grid-cols-3">

                {{-- Latitude --}}
                <div class="rounded-xl border border-slate-200 bg-white p-3">

                    <p class="text-xs text-slate-500">
                        Latitude
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $shipment->proof->latitude ?? 'Not captured' }}
                    </p>

                </div>

                {{-- Longitude --}}
                <div class="rounded-xl border border-slate-200 bg-white p-3">

                    <p class="text-xs text-slate-500">
                        Longitude
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $shipment->proof->longitude ?? 'Not captured' }}
                    </p>

                </div>

                {{-- Delivered At --}}
                <div class="rounded-xl border border-slate-200 bg-white p-3">

                    <p class="text-xs text-slate-500">
                        Delivered At
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $shipment->proof->delivered_at?->format('Y-m-d H:i:s') }}
                    </p>

                </div>

            </div>

        @else

            @if (!$showProofForm)

                <button
                    type="button"
                    wire:click="openProofForm"
                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
                >
                    Add Proof of Delivery
                </button>

           @else

    <div
        x-data="{
            canvas: null,
            ctx: null,
            drawing: false,
            signature: $wire.entangle('signature'),

            init() {
                this.canvas = this.$refs.canvas;
                this.ctx = this.canvas.getContext('2d');

                this.ctx.strokeStyle = '#000000';
                this.ctx.lineWidth = 4;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';

                this.canvas.addEventListener('pointerdown', (event) => {
                    this.drawing = true;

                    const rect = this.canvas.getBoundingClientRect();

                    const x =
                        (event.clientX - rect.left) *
                        (this.canvas.width / rect.width);

                    const y =
                        (event.clientY - rect.top) *
                        (this.canvas.height / rect.height);

                    this.ctx.beginPath();
                    this.ctx.moveTo(x, y);

                    this.canvas.setPointerCapture(event.pointerId);
                });

                this.canvas.addEventListener('pointermove', (event) => {
                    if (!this.drawing) {
                        return;
                    }

                    const rect = this.canvas.getBoundingClientRect();

                    const x =
                        (event.clientX - rect.left) *
                        (this.canvas.width / rect.width);

                    const y =
                        (event.clientY - rect.top) *
                        (this.canvas.height / rect.height);

                    this.ctx.lineTo(x, y);
                    this.ctx.stroke();
                });

                this.canvas.addEventListener('pointerup', () => {
                    this.drawing = false;
                    this.ctx.closePath();
                });

                this.canvas.addEventListener('pointercancel', () => {
                    this.drawing = false;
                });
            },

            clear() {
                this.ctx.clearRect(
                    0,
                    0,
                    this.canvas.width,
                    this.canvas.height
                );

                this.signature = '';
            },

            captureGPS() {
                const status =
                    document.getElementById(
                        'delivery-location-status'
                    );

                if (!navigator.geolocation) {
                    status.textContent =
                        'Geolocation is not supported by this browser.';

                    return;
                }

                status.textContent =
                    'Getting your location...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.$wire.set(
                            'delivery_latitude',
                            position.coords.latitude.toFixed(7)
                        );

                        this.$wire.set(
                            'delivery_longitude',
                            position.coords.longitude.toFixed(7)
                        );

                        status.textContent =
                            `GPS captured: ${
                                position.coords.latitude.toFixed(6)
                            }, ${
                                position.coords.longitude.toFixed(6)
                            }`;
                    },

                    () => {
                        status.textContent =
                            'Unable to capture GPS location. Please allow location access.';
                    },

                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            },

            async save() {
                const signature =
                    this.canvas.toDataURL('image/png');

                await this.$wire.set(
                    'signature',
                    signature
                );

                await this.$wire.saveProof();
            }
        }"
        class="space-y-3"
    >

        {{-- Recipient Name --}}
        <input
            type="text"
            wire:model="recipient_name"
            placeholder="Recipient name"
            class="w-full rounded-xl border px-4 py-2"
        />

        {{-- Delivery Photo --}}
        <input
            type="file"
            wire:model="photo"
            class="w-full"
        />

        {{-- Recipient Signature --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4">

            <div class="flex items-center justify-between gap-3">

                <div>
                    <p class="text-sm font-medium text-slate-900">
                        Recipient Signature
                    </p>

                    <p class="text-xs text-slate-500">
                        Ask the recipient to sign below.
                    </p>
                </div>

                <button
                    type="button"
                    @click="clear()"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Clear
                </button>

            </div>

            <div class="mt-3 overflow-hidden rounded-xl border border-slate-300 bg-white">

                <canvas
                    x-ref="canvas"
                    width="600"
                    height="220"
                    class="block w-full"
                    style="
                        height: 220px;
                        cursor: crosshair;
                        touch-action: none;
                        background: white;
                    "
                ></canvas>

            </div>

            <p class="mt-2 text-xs text-slate-500">
                Sign inside the box above.
            </p>

        </div>

        {{-- Delivery Location --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4">

            <div class="flex items-center justify-between gap-3">

                <div>
                    <p class="text-sm font-medium text-slate-900">
                        Delivery Location
                    </p>

                    <p
                        id="delivery-location-status"
                        class="text-xs text-slate-500"
                    >
                        Location not captured yet.
                    </p>
                </div>

                <button
                    type="button"
                    @click="captureGPS()"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Capture GPS
                </button>

            </div>

            <div class="mt-3 grid grid-cols-2 gap-3">

                <input
                    type="text"
                    wire:model="delivery_latitude"
                    readonly
                    placeholder="Latitude"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                >

                <input
                    type="text"
                    wire:model="delivery_longitude"
                    readonly
                    placeholder="Longitude"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                >

            </div>

        </div>

        {{-- Save Proof --}}
        <button
            type="button"
            @click="save()"
            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
        >
            Save Proof
        </button>

    </div>

@endif
        @endif

    </div>

@endif
            {{-- Add Status Note --}}
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-3">

                <h3 class="font-medium text-slate-900">
                    Add Status Note
                </h3>

                <div class="flex gap-3">

                    <input
                        type="text"
                        wire:model="status_note"
                        placeholder="Enter note or update details..."
                        class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm outline-none"
                    />

                    <button
                        type="button"
                        wire:click="addNote"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Add Note
                    </button>

                </div>

                @error('status_note')
                    <p class="text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </div>

        </div>
         
        {{-- Shipment Items --}}
        <div class="print-section rounded-2xl border border-slate-200 bg-white p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Shipment Items
            </h2>

            @if ($shipment->items->count())

                <div class="mt-5 overflow-x-auto">

                    <table class="print-table w-full text-left text-sm text-slate-600">

                        <thead class="bg-slate-50 text-xs uppercase text-slate-700">

                            <tr>
                                <th class="px-4 py-3">
                                    Description
                                </th>

                                <th class="px-4 py-3">
                                    Weight
                                </th>

                                <th class="px-4 py-3">
                                    Dimensions
                                </th>

                                <th class="px-4 py-3">
                                    Quantity
                                </th>

                                <th class="px-4 py-3">
                                    Fragile
                                </th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach ($shipment->items as $item)

                                <tr>

                                    <td class="px-4 py-3 font-medium text-slate-900">
                                        {{ $item->description }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $item->weight_kg !== null ? $item->weight_kg . ' kg' : '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $item->dimensions ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $item->quantity }}
                                    </td>

                                    <td class="px-4 py-3">

                                        @if ($item->fragile)

                                            <span class="font-semibold text-red-700">
                                                Yes
                                            </span>

                                        @else

                                            <span class="text-slate-600">
                                                No
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <p class="mt-5 text-sm text-slate-500">
                    No items found for this shipment.
                </p>

            @endif

        </div>

        {{-- Status History --}}
        <div class="print-section rounded-2xl border border-slate-200 bg-white p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Status History
            </h2>

            @if ($shipment->statusHistory->count())

                <div class="mt-6 space-y-5">

                    @foreach ($shipment->statusHistory->sortByDesc('created_at') as $history)

                        <div class="flex gap-4">

                            <div class="mt-1 flex h-3 w-3 shrink-0 rounded-full bg-blue-600"></div>

                            <div class="flex-1">

                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">

                                    <p class="font-semibold text-slate-900">
                                        {{ ucfirst(str_replace('_', ' ', $history->status)) }}
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        {{ $history->created_at ? \Illuminate\Support\Carbon::parse($history->created_at)->format('Y-m-d H:i') : '—' }}
                                    </p>

                                </div>

                                @if ($history->note)

                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $history->note }}
                                    </p>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <p class="mt-5 text-sm text-slate-500">
                    No status history available.
                </p>

            @endif

        </div>

    @endif

    {{-- Payment & Rating --}}
    <div class="print-section grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Payment --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 print-card">

            <h2 class="text-lg font-semibold text-slate-900">
                Payment
            </h2>

            @if ($shipment->payment)

                <div class="mt-5 space-y-3 text-sm">

                    <div class="flex justify-between">
                        <span class="text-slate-500">
                            Status
                        </span>

                        <span class="font-semibold text-slate-900">
                            {{ ucfirst($shipment->payment->status ?? '—') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">
                            Amount
                        </span>

                        <span class="font-semibold text-slate-900">
                            ${{ number_format((float) ($shipment->payment->amount ?? 0), 2) }}
                        </span>
                    </div>

                </div>

            @else

                <p class="mt-5 text-sm text-slate-500">
                    No payment information available.
                </p>

            @endif

        </div>

        {{-- Rating --}}
        <div class="no-print rounded-2xl border border-slate-200 bg-white p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Rating
            </h2>

            @if ($shipment->rating)

                <div class="mt-5">

                    <div class="flex items-center gap-1">

                        @for ($i = 1; $i <= 5; $i++)

                            <span class="text-2xl">
                                {{ $i <= $shipment->rating->score ? '★' : '☆' }}
                            </span>

                        @endfor

                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ $shipment->rating->score }} / 5
                    </p>

                    @if ($shipment->rating->comment)

                        <p class="mt-3 text-sm text-slate-600">
                            {{ $shipment->rating->comment }}
                        </p>

                    @endif

                </div>

            @elseif (auth()->user()->role === 'customer' && $shipment->status === 'delivered')

                <form wire:submit="submitRating" class="mt-5 space-y-4">

                    <div>

                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Your Rating
                        </label>

                        <div class="flex gap-2">

                            @for ($i = 1; $i <= 5; $i++)

                                <button
                                    type="button"
                                    wire:click="$set('rating', {{ $i }})"
                                    class="text-3xl transition hover:scale-110
                                        {{ $rating >= $i ? 'text-yellow-400' : 'text-slate-300' }}"
                                >
                                    ★
                                </button>

                            @endfor

                        </div>

                        @error('rating')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div>

                        <label
                            for="rating_comment"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Comment
                            <span class="font-normal text-slate-400">
                                (optional)
                            </span>
                        </label>

                        <textarea
                            id="rating_comment"
                            wire:model="rating_comment"
                            rows="3"
                            maxlength="1000"
                            placeholder="Tell us about your experience..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3
                                   outline-none transition
                                   focus:border-blue-500
                                   focus:ring-4 focus:ring-blue-500/10"
                        ></textarea>

                        @error('rating_comment')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-5 py-3
                               text-sm font-semibold text-white
                               transition hover:bg-blue-700"
                    >
                        Submit Rating
                    </button>

                </form>

            @else

                <p class="mt-5 text-sm text-slate-500">
                    Rating will be available after the shipment is delivered.
                </p>

            @endif

        </div>

    </div>

    {{-- Print Footer --}}
    <div class="print-only mt-8 border-t border-slate-300 pt-4 text-center">

        <p class="text-xs text-slate-500">
            GeoLogistics — Shipment Document
        </p>

        <p class="mt-1 text-xs text-slate-400">
            Tracking Number: {{ $shipment->tracking_number }}
        </p>

    </div>

    {{-- Footer --}}
    <div class="no-print flex justify-end">

        <a
            href="{{ route('shipments.index') }}"
            wire:navigate
            class="rounded-xl border border-slate-300
                   px-5 py-3 text-sm font-semibold
                   text-slate-700 hover:bg-slate-50"
        >
            Back to Shipments
        </a>

    </div>

</div>

@script
<script>
    const canvas = document.getElementById('signature-pad');

    if (canvas) {
        const ctx = canvas.getContext('2d');

        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        let drawing = false;

        function getPosition(event) {
            const rect = canvas.getBoundingClientRect();

            return {
                x: (event.clientX - rect.left)
                    * (canvas.width / rect.width),

                y: (event.clientY - rect.top)
                    * (canvas.height / rect.height),
            };
        }

        canvas.addEventListener('pointerdown', function (event) {
            drawing = true;

            const position = getPosition(event);

            ctx.beginPath();
            ctx.moveTo(position.x, position.y);

            canvas.setPointerCapture(event.pointerId);
        });

        canvas.addEventListener('pointermove', function (event) {
            if (!drawing) {
                return;
            }

            const position = getPosition(event);

            ctx.lineTo(position.x, position.y);
            ctx.stroke();
        });

        canvas.addEventListener('pointerup', function () {
            drawing = false;
            ctx.closePath();
        });

        canvas.addEventListener('pointercancel', function () {
            drawing = false;
        });

        canvas.addEventListener('pointerleave', function () {
            drawing = false;
        });

        const clearButton =
            document.getElementById('clear-signature-button');

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                ctx.clearRect(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );
            });
        }

        const saveButton =
            document.getElementById('save-proof-button');

        if (saveButton) {
            saveButton.addEventListener('click', async function () {
                const signature = canvas.toDataURL('image/png');

                await $wire.set('signature', signature);

                await $wire.saveProof();
            });
        }
    }
</script>
@endscript
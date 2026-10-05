<?php

use Livewire\Component;
use App\Models\Vehicle;

new class extends Component
{
    public string $trackingNumber = '';
    public string $filter = 'all';

    public function track(): void
    {
        $validated = $this->validate([
            'trackingNumber' => ['required', 'string', 'max:255'],
        ], [
            'trackingNumber.required' => 'Please enter a tracking number.',
        ]);

        $this->redirect(
            '/track/' . trim($validated['trackingNumber']),
            navigate: true
        );
    }

    public function getVehiclesProperty()
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'dispatcher', 'driver'])) {
            return collect();
        }

        $query = Vehicle::query()->with(['currentAssignment.driver', 'shipments']);

        if ($user->role === 'driver') {
            $vehicleIds = $user->vehicleAssignments()->where('is_active', true)->pluck('vehicle_id');
            $query->whereIn('id', $vehicleIds);
        }

        return $query->get()->map(function ($vehicle) {
            $activeShipment = $vehicle->shipments->firstWhere(fn ($s) => in_array($s->status, ['assigned', 'picked_up', 'in_transit']));
            return [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'model' => $vehicle->model,
                'status' => $vehicle->status,
                'driver_name' => $vehicle->currentAssignment?->driver?->name ?? 'Unassigned',
                'latitude' => $vehicle->last_location?->getLatitude(),
                'longitude' => $vehicle->last_location?->getLongitude(),
                'speed' => $vehicle->speed ?? 0,
                'current_shipment_number' => $activeShipment?->tracking_number,
            ];
        })->filter(fn ($v) => $v['latitude'] !== null && $v['longitude'] !== null)->values();
    }
};
?>

{{-- تضمين مكتبات Leaflet بآلية Livewire 3 --}}
@assets
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endassets

@if (auth()->check() && in_array(auth()->user()->role, ['admin', 'dispatcher', 'driver']))
    {{-- Live Fleet Map Dashboard --}}
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Live Fleet Map</h1>
                <p class="text-sm text-slate-500">Real-time tracking of active fleet vehicles via WebSockets.</p>
            </div>

            <div class="flex items-center gap-2">
                <button wire:click="$set('filter', 'all')" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">All</button>
                <button wire:click="$set('filter', 'in_transit')" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $filter === 'in_transit' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">On Trip</button>
                <button wire:click="$set('filter', 'idle')" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $filter === 'idle' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">Available</button>
                <button wire:click="$set('filter', 'offline')" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $filter === 'offline' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">Off Duty</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar: Vehicles List -->
            <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm h-[600px] flex flex-col">
                <div class="mb-3">
                    <h2 class="font-semibold text-slate-900 text-sm">Vehicles ({{ count($this->vehicles) }})</h2>
                    <p class="text-xs text-slate-400">Click to focus on map</p>
                </div>

                <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                    @forelse ($this->vehicles as $v)
                        @if ($filter === 'all' || $v['status'] === $filter)
                            <div   data-vehicle-id="{{ $v['id'] }}"
    data-latitude="{{ $v['latitude'] }}"
    data-longitude="{{ $v['longitude'] }}"
onclick="window.focusVehicleFromList(this)"                                 class="p-3 rounded-xl border border-slate-100 bg-slate-50 hover:bg-blue-50/50 cursor-pointer transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-900">{{ $v['plate_number'] }}</span>
                                    <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full 
                                        {{ $v['status'] === 'in_transit' ? 'bg-blue-100 text-blue-700' : ($v['status'] === 'idle' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700') }}">
                                        {{ ucfirst(str_replace('_', ' ', $v['status'])) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500 space-y-1">
                                    <div>Driver: <span class="font-medium text-slate-700">{{ $v['driver_name'] }}</span></div>
                                    @if ($v['current_shipment_number'])
                                        <div>Shipment: <span class="font-mono text-blue-600">{{ $v['current_shipment_number'] }}</span></div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-10 text-xs text-slate-400">No active vehicles found.</div>
                    @endforelse
                </div>
            </div>

            <!-- Map Container -->
            <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div wire:ignore id="fleet-map" class="w-full h-[600px]" style="height: 600px; min-height: 600px;"></div>
            </div>
        </div>
    </div>

    @script
    <script>
        const initialVehicles = @json($this->vehicles);
        let fleetMap = null;
        let fleetMarkers = {};

        function initFleetMap() {
            const mapEl = document.getElementById('fleet-map');
            if (!mapEl || typeof L === 'undefined') return;

            if (fleetMap) {
                fleetMap.remove();
                fleetMap = null;
                fleetMarkers = {};
            } else if (mapEl._leaflet_id) {
                mapEl._leaflet_id = null;
                mapEl.innerHTML = '';
            }

            fleetMap = L.map(mapEl, { zoomControl: true }).setView([33.3152, 44.3661], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(fleetMap);

            initialVehicles.forEach(v => {
                if (v.latitude && v.longitude) {
                    addOrUpdateFleetMarker(v);
                }
            });

            if (Object.keys(fleetMarkers).length > 0) {
                const group = new L.featureGroup(Object.values(fleetMarkers));
                fleetMap.fitBounds(group.getBounds().pad(0.1));
            }

            setTimeout(() => {
                if (fleetMap) fleetMap.invalidateSize();
            }, 250);

    // WebSocket subscription via Echo
if (window.Echo) {
    const userRole = @json(auth()->user()?->role);

    if (userRole === 'driver') {
        const driverVehicleId = initialVehicles.length > 0
            ? initialVehicles[0].id
            : null;

        if (driverVehicleId) {
            window.__fleetChannelSubscription =
                window.Echo.private(`vehicle.${driverVehicleId}`)
                    .listen('.location.updated', (e) => {
                        const vehicle = {
                            id: e.vehicle_id,
                            plate_number: e.plate_number,
                            driver_name: e.driver_name,
                            latitude: e.latitude,
                            longitude: e.longitude,
                            status: e.status,
                            current_shipment_number: e.current_shipment_number,
                        };

                        addOrUpdateFleetMarker(vehicle);
                        updateVehicleListLocation(vehicle);
                    });
        }
    } else {
        window.__fleetChannelSubscription =
            window.Echo.private('fleet')
                .listen('.location.updated', (e) => {
                    const vehicle = {
                        id: e.vehicle_id,
                        plate_number: e.plate_number,
                        driver_name: e.driver_name,
                        latitude: e.latitude,
                        longitude: e.longitude,
                        status: e.status,
                        current_shipment_number: e.current_shipment_number,
                    };

                    addOrUpdateFleetMarker(vehicle);
                    updateVehicleListLocation(vehicle);
                });
    }
}
}
        function updateVehicleListLocation(v) {
    const vehicleItem = document.querySelector(`[data-vehicle-id="${v.id}"]`);

    if (!vehicleItem) return;

    vehicleItem.dataset.latitude = v.latitude;
    vehicleItem.dataset.longitude = v.longitude;
}

        function addOrUpdateFleetMarker(v) {
            if (!fleetMap || !v.latitude || !v.longitude) return;

            const color = v.status === 'in_transit' ? '#2563eb' : (v.status === 'idle' ? '#10b981' : '#64748b');

            const icon = L.divIcon({
                className: 'fleet-marker',
                html: `
                    <div style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:${color};border:3px solid white;box-shadow:0 3px 10px rgba(0,0,0,0.2);font-size:16px;">
                        🚚
                    </div>
                `,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
            });

            const popupContent = `
                <div style="min-width:140px">
                    <strong>${v.plate_number}</strong><br>
                    <span style="color:#64748b">Driver: ${v.driver_name || 'Unassigned'}</span><br>
                    <span style="color:#64748b">Status: ${v.status}</span>
                    ${v.current_shipment_number ? `<br><span style="color:#2563eb">Shipment: ${v.current_shipment_number}</span>` : ''}
                </div>
            `;

            if (fleetMarkers[v.id]) {
                fleetMarkers[v.id].setLatLng([v.latitude, v.longitude]);
                fleetMarkers[v.id].setIcon(icon);
                fleetMarkers[v.id].getPopup().setContent(popupContent);
            } else {
                const marker = L.marker([v.latitude, v.longitude], { icon }).addTo(fleetMap).bindPopup(popupContent);
                fleetMarkers[v.id] = marker;
            }
        }

        window.focusVehicle = function(lat, lng) {
            if (fleetMap && lat && lng) {
                fleetMap.flyTo([lat, lng], 15, { duration: 1.5 });
            }
        };

        document.addEventListener('livewire:navigated', initFleetMap);
        initFleetMap();

       document.addEventListener('livewire:navigating', () => {
    if (window.__fleetChannelSubscription && window.Echo) {
        window.Echo.leave('fleet');
        window.__fleetChannelSubscription = null;
    }

    if (fleetMap) {
        fleetMap.remove();
        fleetMap = null;
        fleetMarkers = {};
    }
});

window.focusVehicleFromList = function (element) {
    const lat = parseFloat(element.dataset.latitude);
    const lng = parseFloat(element.dataset.longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        console.log('Invalid vehicle coordinates:', element.dataset);
        return;
    }

    window.focusVehicle(lat, lng);

}
    </script>
    @endscript

@else
    {{-- Public Shipment Tracking Search (Guest / Customer) --}}
    <div class="min-h-[80vh] flex items-center justify-center px-4">
        <div class="w-full max-w-xl">
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50">
                        <svg class="h-7 w-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/>
                            <circle cx="7" cy="19" r="1.5"/>
                            <circle cx="18" cy="19" r="1.5"/>
                        </svg>
                    </div>
                    <h1 class="mt-5 text-2xl font-bold text-slate-900">Track Your Shipment</h1>
                    <p class="mt-2 text-sm text-slate-500">Enter your tracking number to see the current status, location and delivery progress.</p>
                </div>

                <form wire:submit="track" class="mt-8 space-y-4">
                    <div>
                        <label for="tracking-number" class="mb-2 block text-sm font-medium text-slate-700">Tracking Number</label>
                        <input id="tracking-number" type="text" wire:model="trackingNumber" placeholder="e.g. GL-2026-XXXXXX"
                               class="w-full rounded-xl border border-slate-300 px-4 py-3 text-center text-lg font-semibold tracking-wide outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    @error('trackingNumber')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Track Shipment
                    </button>
                </form>
            </div>
            <p class="mt-6 text-center text-xs text-slate-400">You can find the tracking number in your booking confirmation or shipment invoice.</p>
        </div>
    </div>
@endif
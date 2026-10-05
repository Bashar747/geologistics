<?php

use App\Models\Geofence;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Data\Geometries\Polygon;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $type = 'warehouse';

    public string $pointsJson = '[]';

    public function save(): void
    {
        $points = json_decode($this->pointsJson, true) ?: [];

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:warehouse,restricted_zone,delivery_area'],
            'pointsJson' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($points) {
                    if (count($points) < 3) {
                        $fail('Please draw a polygon with at least 3 points on the map.');

                        return;
                    }

                    foreach ($points as $point) {
                        $lat = $point['lat'] ?? null;
                        $lng = $point['lng'] ?? null;

                        if (
                            ! is_numeric($lat) || $lat < -90 || $lat > 90 ||
                            ! is_numeric($lng) || $lng < -180 || $lng > 180
                        ) {
                            $fail('All points must have a valid latitude and longitude.');

                            return;
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Please enter a geofence name.',
            'type.in' => 'Please select a valid type.',
        ]);

        $coordinates = collect($points)
            ->map(fn ($p) => Point::make((float)$p['lng'], (float)$p['lat'], srid: 4326))
            ->values()
            ->all();

        $first = $points[0];
        $last = end($points);

        if ((float)$first['lat'] !== (float)$last['lat'] || (float)$first['lng'] !== (float)$last['lng']) {
            $coordinates[] = Point::make((float)$first['lng'], (float)$first['lat'], srid: 4326);
        }

        $ring = LineString::make($coordinates, srid: 4326);

        Geofence::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'area_polygon' => Polygon::make([$ring], srid: 4326),
        ]);

        session()->flash('success', 'Geofence created successfully.');

        $this->redirect('/geofences', navigate: true);
    }
};
?>

@assets
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endassets

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Create Geofence
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Draw a geographic zone on the map.
            </p>
        </div>

        <a
            href="/geofences"
            wire:navigate
            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
        >
            ← Back to Geofences
        </a>
    </div>

    {{-- Form --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

        <form wire:submit="save" class="space-y-5">

            {{-- Name --}}
            <div>
                <label for="geofence-name" class="mb-2 block text-sm font-medium text-slate-700">
                    Name
                </label>
                <input
                    id="geofence-name"
                    type="text"
                    wire:model="name"
                    placeholder="e.g. North Warehouse Zone"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type --}}
            <div>
                <label for="geofence-type" class="mb-2 block text-sm font-medium text-slate-700">
                    Type
                </label>
                <select
                    id="geofence-type"
                    wire:model="type"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
                    <option value="warehouse">Warehouse</option>
                    <option value="restricted_zone">Restricted Zone</option>
                    <option value="delivery_area">Delivery Area</option>
                </select>
                @error('type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Map Section --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700">
                        Area Polygon
                    </label>
                    <span id="geofence-vertex-counter" class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        0 vertices
                    </span>
                </div>

                {{-- Map Container --}}
                <div
                    wire:ignore
                    id="geofence-map-container"
                    class="h-96 w-full rounded-xl border border-slate-300"
                    style="min-height: 380px;"
                ></div>

                <p class="mt-2 text-xs text-slate-500">
                    Click on the map to add vertices. Minimum 3 points required.
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        type="button"
                        id="btn-undo-vertex"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        ← Undo Last Vertex
                    </button>

                    <button
                        type="button"
                        id="btn-clear-vertices"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Clear All
                    </button>
                </div>

                @error('pointsJson')
                    <div class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-600 border border-red-200 font-medium">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    <span wire:loading.remove>Create Geofence</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>

        </form>

    </div>

</div>

@script
<script>
    function renderMap() {
        const mapContainer = document.getElementById('geofence-map-container');
        if (!mapContainer || typeof L === 'undefined') return;

        if (mapContainer._leaflet_id) {
            mapContainer._leaflet_id = null;
            mapContainer.innerHTML = '';
        }

        const map = L.map(mapContainer, {
            zoomControl: true,
            scrollWheelZoom: true,
        }).setView([33.3152, 44.3661], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        setTimeout(() => map.invalidateSize(), 250);

        const drawnPoints = [];
        let polygonLayer = null;
        const verticesGroup = L.layerGroup().addTo(map);

        function updateLivewire() {
            const formatted = drawnPoints.map(p => ({ lat: p[0], lng: p[1] }));
            $wire.set('pointsJson', JSON.stringify(formatted));

            const counter = document.getElementById('geofence-vertex-counter');
            if (counter) {
                counter.textContent = drawnPoints.length + ' vertices';
            }
        }

        function redraw() {
            if (polygonLayer) {
                polygonLayer.remove();
                polygonLayer = null;
            }

            verticesGroup.clearLayers();

            if (drawnPoints.length > 0) {
                polygonLayer = L.polygon(drawnPoints, {
                    color: '#2563eb',
                    fillColor: '#2563eb',
                    fillOpacity: 0.2,
                    weight: 2,
                }).addTo(map);

                drawnPoints.forEach((point, index) => {
                    L.circleMarker(point, {
                        radius: 5,
                        color: '#1e40af',
                        fillColor: '#ffffff',
                        fillOpacity: 1,
                        weight: 2,
                    })
                    .addTo(verticesGroup)
                    .bindTooltip('#' + (index + 1));
                });
            }
        }

        map.on('click', function (e) {
            drawnPoints.push([e.latlng.lat, e.latlng.lng]);
            redraw();
            updateLivewire();
        });

        const undoBtn = document.getElementById('btn-undo-vertex');
        if (undoBtn) {
            undoBtn.onclick = function () {
                drawnPoints.pop();
                redraw();
                updateLivewire();
            };
        }

        const clearBtn = document.getElementById('btn-clear-vertices');
        if (clearBtn) {
            clearBtn.onclick = function () {
                drawnPoints.length = 0;
                redraw();
                updateLivewire();
            };
        }
    }

    document.addEventListener('livewire:navigated', renderMap);
    renderMap();
</script>
@endscript
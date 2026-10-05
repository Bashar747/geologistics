<?php

use App\Models\Geofence;
use Livewire\Component;

new class extends Component
{
    public Geofence $geofence;

    public function mount(Geofence $geofence): void
    {
        $this->geofence = $geofence;
    }

    public function getVertexCountProperty(): int
    {
        $ring = $this->geofence->area_polygon?->getLineStrings()[0] ?? null;

        if (! $ring) {
            return 0;
        }

        $count = $ring->count();

        $points = $ring->getPoints();

        if ($count > 1) {
            $first = (string) $points[0];
            $last = (string) end($points);

            if ($first === $last) {
                return $count - 1;
            }
        }

        return $count;
    }

    public function deleteGeofence(): void
    {
        // استخدام Policy الصلاحيات الموحد لضمان الأمان
        $this->authorize('delete', $this->geofence);

        $this->geofence->delete();

        session()->flash('success', 'Geofence deleted successfully.');

        $this->redirect('/geofences', navigate: true);
    }
};
?>

{{-- استخدام directive الخاص بـ Livewire 3 للـ Assets --}}
@assets
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endassets

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $this->geofence->name }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Geographic zone details and area preview.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">

            <a
                href="/geofences"
                wire:navigate
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                ← Back to Geofences
            </a>

            @can('update', $this->geofence)
                <a
                    href="/geofences/{{ $this->geofence->id }}/edit"
                    wire:navigate
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    Edit
                </a>
            @endcan

            @can('delete', $this->geofence)
                <button
                    type="button"
                    wire:click="deleteGeofence"
                    wire:confirm="Are you sure you want to delete this geofence?"
                    class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                >
                    Delete
                </button>
            @endcan

        </div>

    </div>

    {{-- Info Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-slate-900">
            Geofence Information
        </h2>

        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Name
                </p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->geofence->name }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Type
                </p>

                <p class="mt-1">
                    @php
                        $typeClasses = match ($this->geofence->type) {
                            'warehouse' => 'bg-emerald-100 text-emerald-700',
                            'restricted_zone' => 'bg-red-100 text-red-700',
                            'delivery_area' => 'bg-blue-100 text-blue-700',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $typeLabel = match ($this->geofence->type) {
                            'restricted_zone' => 'Restricted Zone',
                            'delivery_area' => 'Delivery Area',
                            default => ucfirst($this->geofence->type),
                        };
                    @endphp

                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $typeClasses }}">
                        {{ $typeLabel }}
                    </span>
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Vertices
                </p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->vertexCount }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Created
                </p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->geofence->created_at?->format('M j, Y') ?? 'N/A' }}
                </p>
            </div>

        </div>

    </div>

    {{-- Map Container --}}
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-slate-900">
                Area Polygon
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                The geographic area covered by this geofence.
            </p>
        </div>

        <div class="p-6">
            <div
                wire:ignore
                id="geofence-show-map"
                class="h-80 w-full rounded-xl border border-slate-300"
                style="min-height: 320px;"
            ></div>
        </div>

    </div>

</div>

@script
<script>
    let showMapInstance = null;

    function initializeGeofenceShowMap() {
        const mapElement = document.getElementById('geofence-show-map');
        if (!mapElement || typeof L === 'undefined') return;

        // تنظيف الخريطة السابقة لمنع التكرار وأخطاء الذاكرة
        if (showMapInstance) {
            showMapInstance.remove();
            showMapInstance = null;
        } else if (mapElement._leaflet_id) {
            mapElement._leaflet_id = null;
            mapElement.innerHTML = '';
        }

        const ringPoints = @json(
            collect($geofence->area_polygon?->getLineStrings()[0]?->getPoints() ?? [])
                ->map(fn ($point) => [
                    'lat' => $point->getLatitude(),
                    'lng' => $point->getLongitude(),
                ])
                ->values()
                ->all()
        );

        const latLngs = ringPoints.map(p => [p.lat, p.lng]);

        showMapInstance = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(showMapInstance);

        if (latLngs.length > 0) {
            const polygon = L.polygon(latLngs, {
                color: '#2563eb',
                fillColor: '#2563eb',
                fillOpacity: 0.2,
                weight: 2,
            }).addTo(showMapInstance);

            latLngs.forEach((point, index) => {
                L.circleMarker(point, {
                    radius: 5,
                    color: '#1e40af',
                    fillColor: '#ffffff',
                    fillOpacity: 1,
                    weight: 2,
                })
                .addTo(showMapInstance)
                .bindTooltip('#' + (index + 1));
            });

            showMapInstance.fitBounds(polygon.getBounds(), {
                padding: [30, 30],
            });
        } else {
            showMapInstance.setView([33.3152, 44.3661], 11);
        }

        setTimeout(() => {
            if (showMapInstance) {
                showMapInstance.invalidateSize();
            }
        }, 250);
    }

    document.addEventListener('livewire:navigated', initializeGeofenceShowMap);
    initializeGeofenceShowMap();
</script>
@endscript
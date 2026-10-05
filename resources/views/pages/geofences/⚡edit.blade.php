<?php

use App\Models\Geofence;
use Livewire\Component;

new class extends Component
{
    public Geofence $geofence;

    public string $name = '';

    public string $type = '';

    public function mount(Geofence $geofence): void
    {
        $this->geofence = $geofence;

        $this->name = $geofence->name;

        $this->type = $geofence->type;
    }

    public function update(): void
    {
        // فحص الصلاحية لحماية الإجراء
        $this->authorize('update', $this->geofence);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:warehouse,restricted_zone,delivery_area'],
        ], [
            'name.required' => 'Please enter a name.',
            'type.in' => 'Please select a valid type.',
        ]);

        $this->geofence->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        session()->flash('success', 'Geofence updated successfully.');

        $this->redirect('/geofences/' . $this->geofence->id, navigate: true);
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
                Edit Geofence
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Update the geofence name and type.
            </p>
        </div>

        <a
            href="/geofences/{{ $this->geofence->id }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
        >
            ← Back to Geofence
        </a>
    </div>

    {{-- Form --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <form wire:submit="update" class="space-y-5">

            {{-- Name --}}
            <div>
                <label for="geofence-name" class="mb-2 block text-sm font-medium text-slate-700">
                    Name
                </label>
                <input
                    id="geofence-name"
                    type="text"
                    wire:model="name"
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

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="update">Save Changes</span>
                    <span wire:loading wire:target="update">Saving...</span>
                </button>
            </div>

        </form>
    </div>

    {{-- Read-only map --}}
    <div class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-slate-900">
                Area Polygon
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                The polygon shape cannot be edited.
            </p>
        </div>

        <div class="p-6">
            <div
                wire:ignore
                id="geofence-edit-map"
                class="h-80 w-full rounded-xl border border-slate-300"
                style="min-height: 320px;"
            ></div>
        </div>
    </div>

</div>

@script
<script>
    let editMapInstance = null;

    function initializeGeofenceEditMap() {
        const mapElement = document.getElementById('geofence-edit-map');
        if (!mapElement || typeof L === 'undefined') return;

        // تنظيف الخريطة السابقة بشكل صحيح
        if (editMapInstance) {
            editMapInstance.remove();
            editMapInstance = null;
        } else if (mapElement._leaflet_id) {
            mapElement._leaflet_id = null;
            mapElement.innerHTML = '';
        }

        // استخراج الإحداثيات
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

        // إنشاء الخريطة
        editMapInstance = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(editMapInstance);

        if (latLngs.length > 0) {
            const polygon = L.polygon(latLngs, {
                color: '#2563eb',
                fillColor: '#2563eb',
                fillOpacity: 0.2,
                weight: 2,
                interactive: false,
            }).addTo(editMapInstance);

            editMapInstance.fitBounds(polygon.getBounds(), {
                padding: [30, 30],
            });
        } else {
            editMapInstance.setView([33.3152, 44.3661], 11);
        }

        // ضبط الحجم بعد تحميل الصفحة
        setTimeout(() => {
            if (editMapInstance) {
                editMapInstance.invalidateSize();
            }
        }, 250);
    }

    document.addEventListener('livewire:navigated', initializeGeofenceEditMap);
    initializeGeofenceEditMap();
</script>
@endscript
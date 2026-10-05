<?php

use App\Models\Geofence;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->type = '';

        $this->resetPage();
    }

    public function getGeofencesProperty()
    {
        return Geofence::query()
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where('name', 'like', $search);
            })
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function getVertexCount(Geofence $geofence): int
    {
        $ring = $geofence->area_polygon?->getLineStrings()[0] ?? null;

        if (! $ring) {
            return 0;
        }

        $count = $ring->count();

        // الحلقة المخزنة مغلقة: أول نقطة = آخر نقطة، فنطرحها من العد
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

    public function deleteGeofence(int $geofenceId): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to delete geofences.');

            return;
        }

        Geofence::findOrFail($geofenceId)->delete();

        session()->flash('success', 'Geofence deleted successfully.');
    }

    private function canManage(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'dispatcher'], true);
    }
};
?>

<div class="space-y-6">

    {{-- ============================================================ --}}
    {{-- Header --}}
    {{-- ============================================================ --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                Geofences
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Geographic zones: warehouses, restricted areas and delivery areas.
            </p>

        </div>

        @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

            <a
                href="/geofences/create"
                wire:navigate
                class="inline-flex items-center justify-center gap-2
                       rounded-xl bg-blue-600 px-5 py-3
                       text-sm font-semibold text-white
                       transition hover:bg-blue-700"
            >
                <span class="text-lg leading-none">+</span>

                Create Geofence
            </a>

        @endif

    </div>


    {{-- ============================================================ --}}
    {{-- Flash Messages --}}
    {{-- ============================================================ --}}

    @if (session('success'))

        <div
            class="rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif

    @if (session('error'))

        <div
            class="rounded-xl border border-red-200
                   bg-red-50 px-4 py-3
                   text-sm text-red-700"
        >
            {{ session('error') }}
        </div>

    @endif


    {{-- ============================================================ --}}
    {{-- Filters --}}
    {{-- ============================================================ --}}

    <div
        class="rounded-2xl border border-slate-200
               bg-white p-5"
    >

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Search --}}
            <div class="md:col-span-2">

                <label
                    for="geofence-search"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Search
                </label>

                <div class="relative">

                    <span
                        class="pointer-events-none absolute
                               left-4 top-1/2 -translate-y-1/2
                               text-slate-400"
                    >
                        🔍
                    </span>

                    <input
                        id="geofence-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name..."
                        class="w-full rounded-xl border
                               border-slate-300
                               px-4 py-3 pl-11
                               outline-none transition
                               focus:border-blue-500
                               focus:ring-4
                               focus:ring-blue-500/10"
                    >

                </div>

            </div>


            {{-- Type --}}
            <div>

                <label
                    for="geofence-type"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Type
                </label>

                <select
                    id="geofence-type"
                    wire:model.live="type"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

                    <option value="">
                        All types
                    </option>

                    <option value="warehouse">
                        Warehouse
                    </option>

                    <option value="restricted_zone">
                        Restricted Zone
                    </option>

                    <option value="delivery_area">
                        Delivery Area
                    </option>

                </select>

            </div>

        </div>


        {{-- Clear --}}
        @if ($search || $type)

            <div class="mt-4">

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-sm font-semibold text-blue-600
                           hover:text-blue-700"
                >
                    Clear filters
                </button>

            </div>

        @endif

    </div>


    {{-- ============================================================ --}}
    {{-- Geofences Table --}}
    {{-- ============================================================ --}}

    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider
                               text-slate-500"
                    >

                        <th class="px-6 py-4">
                            Name
                        </th>

                        <th class="px-6 py-4">
                            Type
                        </th>

                        <th class="px-6 py-4">
                            Area
                        </th>

                        <th class="px-6 py-4">
                            Created
                        </th>

                        <th class="px-6 py-4 text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->geofences as $geofence)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Name --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <div class="font-semibold text-slate-900">
                                    {{ $geofence->name }}
                                </div>

                                <div class="mt-1 text-xs text-slate-400">
                                    #{{ $geofence->id }}
                                </div>

                            </td>


                            {{-- Type --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $typeClasses = match ($geofence->type) {
                                        'warehouse' => 'bg-emerald-100 text-emerald-700',
                                        'restricted_zone' => 'bg-red-100 text-red-700',
                                        'delivery_area' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };

                                    $typeLabel = match ($geofence->type) {
                                        'restricted_zone' => 'Restricted Zone',
                                        'delivery_area' => 'Delivery Area',
                                        default => ucfirst($geofence->type),
                                    };
                                @endphp

                                <span
                                    class="inline-flex rounded-full
                                           px-3 py-1 text-xs
                                           font-semibold {{ $typeClasses }}"
                                >
                                    {{ $typeLabel }}
                                </span>

                            </td>


                            {{-- Area --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <span class="text-sm text-slate-600">
                                    {{ $this->getVertexCount($geofence) }}
                                    vertices
                                </span>

                            </td>


                            {{-- Created --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <span class="text-sm text-slate-600">
                                    {{ $geofence->created_at->format('M j, Y') }}
                                </span>

                            </td>


                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-6 py-5 text-right">

                                <div class="inline-flex items-center gap-2">

                                    <a
                                        href="/geofences/{{ $geofence->id }}"
                                        wire:navigate
                                        class="rounded-lg border
                                               border-slate-200
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-slate-700
                                               transition
                                               hover:bg-slate-50"
                                    >
                                        View
                                    </a>

                                    @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

                                        <button
                                            type="button"
                                            wire:click="deleteGeofence({{ $geofence->id }})"
                                            wire:confirm="Are you sure you want to delete this geofence?"
                                            class="rounded-lg border
                                                   border-red-200
                                                   px-3 py-2
                                                   text-xs font-semibold
                                                   text-red-600
                                                   transition
                                                   hover:bg-red-50"
                                        >
                                            Delete
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="px-6 py-12 text-center">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No geofences found
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Try changing your search or filters.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ======================================================== --}}
        {{-- Pagination --}}
        {{-- ======================================================== --}}

        @if ($this->geofences->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->geofences->links() }}

            </div>

        @endif

    </div>

</div>

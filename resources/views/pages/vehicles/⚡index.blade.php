<?php

use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    /*
    |--------------------------------------------------------------------------
    | Reset Pagination
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Vehicle
    |--------------------------------------------------------------------------
    */

    public function deleteVehicle(int $vehicleId): void
    {
        $vehicle = Vehicle::find($vehicleId);

        if (! $vehicle) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Don't delete a vehicle that has active assignments
        |--------------------------------------------------------------------------
        */

        if ($vehicle->currentAssignment()->exists()) {
            $this->addError(
                'delete',
                'You cannot delete a vehicle that is currently assigned to a driver.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Don't delete a vehicle with active shipments
        |--------------------------------------------------------------------------
        */

        if ($vehicle->shipments()
            ->whereIn('status', ['assigned', 'picked_up'])
            ->exists()
        ) {
            $this->addError(
                'delete',
                'You cannot delete a vehicle with active shipments.'
            );

            return;
        }

        $vehicle->delete();

        session()->flash(
            'success',
            'Vehicle deleted successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Vehicles
    |--------------------------------------------------------------------------
    */

    public function getVehiclesProperty()
    {
        return Vehicle::query()
            ->with([
                'currentAssignment.driver:id,name,phone',
            ])
            ->when(
                $this->search !== '',
                function ($query) {
                    $query->where(function ($q) {
                        $q->where(
                            'plate_number',
                            'like',
                            '%' . $this->search . '%'
                        )
                        ->orWhere(
                            'model',
                            'like',
                            '%' . $this->search . '%'
                        )
                        ->orWhere(
                            'type',
                            'like',
                            '%' . $this->search . '%'
                        );
                    });
                }
            )
            ->when(
                $this->status !== '',
                fn ($query) =>
                    $query->where('status', $this->status)
            )
            ->latest()
            ->paginate(15);
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

            <h1 class="text-2xl font-bold text-slate-900">
                Vehicles
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage your fleet and vehicle information.
            </p>

        </div>


        <a
            href="{{ route('vehicles.create') }}"
            wire:navigate
            class="inline-flex items-center
                   justify-center gap-2
                   rounded-xl bg-blue-600
                   px-5 py-3
                   text-sm font-semibold
                   text-white
                   transition hover:bg-blue-700"
        >

            <span class="text-lg leading-none">
                +
            </span>

            Create Vehicle

        </a>

    </div>


    {{-- ============================================================= --}}
    {{-- Success Message --}}
    {{-- ============================================================= --}}

    @if (session('success'))

        <div
            class="rounded-xl border
                   border-emerald-200
                   bg-emerald-50
                   px-4 py-3
                   text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ============================================================= --}}
    {{-- Delete Error --}}
    {{-- ============================================================= --}}

    @error('delete')

        <div
            class="rounded-xl border
                   border-red-200
                   bg-red-50
                   px-4 py-3
                   text-sm text-red-700"
        >
            {{ $message }}
        </div>

    @enderror


    {{-- ============================================================= --}}
    {{-- Filters --}}
    {{-- ============================================================= --}}

    <div
        class="rounded-2xl border
               border-slate-200
               bg-white p-5"
    >

        <div
            class="grid grid-cols-1 gap-4
                   md:grid-cols-3"
        >

            {{-- Search --}}
            <div class="md:col-span-2">

                <label
                    for="vehicle-search"
                    class="mb-2 block text-sm
                           font-medium text-slate-700"
                >
                    Search
                </label>

                <input
                    id="vehicle-search"
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by plate number, model or type..."
                    class="w-full rounded-xl
                           border border-slate-300
                           bg-white px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

            </div>


            {{-- Status --}}
            <div>

                <label
                    for="vehicle-status"
                    class="mb-2 block text-sm
                           font-medium text-slate-700"
                >
                    Status
                </label>

                <select
                    id="vehicle-status"
                    wire:model.live="status"
                    class="w-full rounded-xl
                           border border-slate-300
                           bg-white px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

                    <option value="">
                        All statuses
                    </option>

                    <option value="idle">
                        Idle
                    </option>

                    <option value="in_transit">
                        In Transit
                    </option>

                    <option value="maintenance">
                        Maintenance
                    </option>

                    <option value="offline">
                        Offline
                    </option>

                </select>

            </div>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- Vehicles Table --}}
    {{-- ============================================================= --}}

    <div
        class="overflow-hidden rounded-2xl
               border border-slate-200
               bg-white"
    >

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
                            Vehicle
                        </th>

                        <th
                            class="px-6 py-4 text-left
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Type
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
                            Current Driver
                        </th>

                        <th
                            class="px-6 py-4 text-right
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="divide-y divide-slate-100"
                >

                    @forelse ($this->vehicles as $vehicle)

                        <tr
                            wire:key="vehicle-{{ $vehicle->id }}"
                            class="transition hover:bg-slate-50"
                        >

                            {{-- Vehicle --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <div>

                                    <div
                                        class="font-semibold
                                               text-slate-900"
                                    >
                                        {{ $vehicle->plate_number }}
                                    </div>

                                    <div
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        {{ $vehicle->model ?: 'No model specified' }}
                                    </div>

                                </div>

                            </td>


                            {{-- Type --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4 text-sm
                                       text-slate-600"
                            >
                                {{ $vehicle->type ?: '—' }}
                            </td>


                            {{-- Status --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4"
                            >

                                @if ($vehicle->status === 'idle')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-emerald-100
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-emerald-700"
                                    >
                                        Idle
                                    </span>

                                @elseif ($vehicle->status === 'in_transit')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-blue-100
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-blue-700"
                                    >
                                        In Transit
                                    </span>

                                @elseif ($vehicle->status === 'maintenance')

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-amber-100
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-amber-700"
                                    >
                                        Maintenance
                                    </span>

                                @else

                                    <span
                                        class="inline-flex
                                               rounded-full
                                               bg-slate-200
                                               px-3 py-1
                                               text-xs font-semibold
                                               text-slate-700"
                                    >
                                        Offline
                                    </span>

                                @endif

                            </td>


                            {{-- Driver --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4"
                            >

                                @if ($vehicle->currentAssignment?->driver)

                                    <div>

                                        <div
                                            class="font-medium
                                                   text-slate-900"
                                        >
                                            {{ $vehicle->currentAssignment->driver->name }}
                                        </div>

                                        <div
                                            class="mt-1 text-xs
                                                   text-slate-500"
                                        >
                                            {{ $vehicle->currentAssignment->driver->phone }}
                                        </div>

                                    </div>

                                @else

                                    <span
                                        class="text-sm
                                               text-slate-400"
                                    >
                                        Not assigned
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td
                                class="whitespace-nowrap
                                       px-6 py-4 text-right"
                            >

                                <div
                                    class="flex items-center
                                           justify-end gap-2"
                                >

                                    {{-- View --}}
                                    <a
                                        href="{{ route('vehicles.show', $vehicle) }}"
                                        wire:navigate
                                        class="rounded-lg
                                               border border-slate-200
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-slate-700
                                               transition
                                               hover:bg-slate-50"
                                    >
                                        View
                                    </a>


                                    {{-- Edit --}}
                                    <a
                                        href="{{ route('vehicles.edit', $vehicle) }}"
                                        wire:navigate
                                        class="rounded-lg
                                               border border-blue-200
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-blue-600
                                               transition
                                               hover:bg-blue-50"
                                    >
                                        Edit
                                    </a>


                                    {{-- Delete --}}
                                    <button
                                        type="button"
                                        wire:click="deleteVehicle({{ $vehicle->id }})"
                                        wire:confirm="Are you sure you want to delete this vehicle?"
                                        class="rounded-lg
                                               border border-red-200
                                               px-3 py-2
                                               text-xs font-semibold
                                               text-red-600
                                               transition
                                               hover:bg-red-50"
                                    >
                                        Delete
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="px-6 py-12
                                       text-center"
                            >

                                <div
                                    class="mx-auto max-w-sm"
                                >

                                    <div
                                        class="text-4xl"
                                    >
                                        🚚
                                    </div>

                                    <h3
                                        class="mt-3
                                               font-semibold
                                               text-slate-900"
                                    >
                                        No vehicles found
                                    </h3>

                                    <p
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        Try changing your search
                                        or create a new vehicle.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ========================================================= --}}
        {{-- Pagination --}}
        {{-- ========================================================= --}}

        @if ($this->vehicles->hasPages())

            <div
                class="border-t border-slate-200
                       px-6 py-4"
            >
                {{ $this->vehicles->links() }}
            </div>

        @endif

    </div>

</div>
<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public int $perPage = 15;
    
    public function mount(): void
{
    if (! in_array(auth()->user()->role, ['admin', 'dispatcher'], true)) {
        abort(403);
    }
}
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';

        $this->resetPage();
    }

    public function getDriversProperty()
    {
        return User::query()
            ->where('role', 'driver')
            ->with([
                'driverProfile:id,user_id,license_number,license_expiry,rating_avg,status',
                'vehicleAssignments' => function ($query) {
                    $query->where('is_active', true)
                        ->with('vehicle:id,plate_number,model');
                },
            ])
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($this->status, function ($query) {
                $query->whereHas('driverProfile', function ($query) {
                    $query->where('status', $this->status);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }
};
?>

<div class="space-y-6">

    {{-- ============================================================ --}}
    {{-- Header --}}
    {{-- ============================================================ --}}

    <div>

        <h1 class="text-2xl font-bold text-slate-900">
            Drivers
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Manage drivers, their status and vehicle assignments.
        </p>

    </div>


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
                    for="driver-search"
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
                        id="driver-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name or phone..."
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


            {{-- Status --}}
            <div>

                <label
                    for="driver-status"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Status
                </label>

                <select
                    id="driver-status"
                    wire:model.live="status"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

                    <option value="">
                        All statuses
                    </option>

                    <option value="available">
                        Available
                    </option>

                    <option value="on_duty">
                        On Duty
                    </option>

                    <option value="suspended">
                        Suspended
                    </option>

                </select>

            </div>

        </div>


        {{-- Clear --}}
        @if ($search || $status)

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
    {{-- Drivers Table --}}
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
                            Phone
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                        <th class="px-6 py-4">
                            Rating
                        </th>

                        <th class="px-6 py-4">
                            License Expiry
                        </th>

                        <th class="px-6 py-4">
                            Current Vehicle
                        </th>

                        <th class="px-6 py-4 text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->drivers as $driver)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Name --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-9 w-9
                                               shrink-0 items-center
                                               justify-center
                                               rounded-full bg-blue-100
                                               text-sm font-bold
                                               text-blue-700"
                                    >
                                        {{ strtoupper(substr($driver->name, 0, 1)) }}
                                    </div>

                                    <div class="font-semibold text-slate-900">
                                        {{ $driver->name }}
                                    </div>

                                </div>

                            </td>


                            {{-- Phone --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($driver->phone)

                                    <span class="text-sm text-slate-600">
                                        {{ $driver->phone }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $statusClasses = match ($driver->driverProfile?->status) {
                                        'available' => 'bg-emerald-100 text-emerald-700',
                                        'on_duty' => 'bg-blue-100 text-blue-700',
                                        'suspended' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };

                                    $statusLabel = match ($driver->driverProfile?->status) {
                                        'on_duty' => 'On Duty',
                                        default => ucfirst($driver->driverProfile?->status ?? 'No profile'),
                                    };
                                @endphp

                                <span
                                    class="inline-flex rounded-full
                                           px-3 py-1 text-xs
                                           font-semibold
                                           {{ $statusClasses }}"
                                >
                                    {{ $statusLabel }}
                                </span>

                            </td>


                            {{-- Rating --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($driver->driverProfile)

                                    <span class="text-sm font-semibold text-slate-900">
                                        {{ number_format((float) $driver->driverProfile->rating_avg, 2) }}
                                    </span>

                                    <span class="text-xs text-slate-400">
                                        / 5
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- License Expiry --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($driver->driverProfile?->license_expiry)

                                    <span class="text-sm text-slate-600">
                                        {{ $driver->driverProfile->license_expiry->format('M j, Y') }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Current Vehicle --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $currentAssignment = $driver->vehicleAssignments
                                        ->firstWhere('is_active', true);
                                @endphp

                                @if ($currentAssignment?->vehicle)

                                    <div class="font-medium text-slate-900">
                                        {{ $currentAssignment->vehicle->plate_number }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $currentAssignment->vehicle->model }}
                                    </div>

                                @else

                                    <span
                                        class="inline-flex rounded-full
                                               bg-slate-100 px-3 py-1
                                               text-xs font-medium
                                               text-slate-600"
                                    >
                                        Not Assigned
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-6 py-5 text-right">

                                <a
                                    href="/drivers/{{ $driver->id }}"
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

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7" class="px-6 py-12 text-center">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No drivers found
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

        @if ($this->drivers->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->drivers->links() }}

            </div>

        @endif

    </div>

</div>

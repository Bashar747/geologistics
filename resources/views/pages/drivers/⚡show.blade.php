<?php

use App\Models\DriverVehicleAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public User $driver;

    public int $selectedVehicleId = 0;

    public string $selectedStatus = '';

    public function mount(User $driver): void
    {
        $user = auth()->user();

        if ($driver->role !== 'driver') {
            abort(404);
        }

        // نفس قاعدة الـ API: السائق يشوف صفحته بس، الأدمن/الموزّع يشوفوا الكل
        if (
            $user->role === 'driver' && $user->id !== $driver->id
        ) {
            abort(403);
        }

        if (! in_array($user->role, ['admin', 'dispatcher', 'driver'], true)) {
            abort(403);
        }

        $this->driver = $driver->load([
            'driverProfile',
            'vehicleAssignments' => function ($query) {
                $query->latest('assigned_at')
                    ->with('vehicle:id,plate_number,model,type');
            },
        ]);

        $this->selectedStatus = $this->driver->driverProfile?->status ?? '';
    }

    public function getCurrentAssignmentProperty()
    {
        return $this->driver->vehicleAssignments
            ->firstWhere('is_active', true);
    }

    public function getAssignmentHistoryProperty()
    {
        return $this->driver->vehicleAssignments;
    }

    public function getAvailableVehiclesProperty()
    {
        return Vehicle::query()
            ->select('id', 'plate_number', 'model')
            ->with('currentAssignment.driver:id,name')
            ->orderBy('plate_number')
            ->get();
    }

    private function canManage(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'dispatcher'], true);
    }

    public function assignVehicle(): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to manage assignments.');

            return;
        }

        $validated = $this->validate([
            'selectedVehicleId' => ['required', 'integer', 'exists:vehicles,id'],
        ], [
            'selectedVehicleId.required' => 'Please select a vehicle.',
            'selectedVehicleId.exists' => 'Please select a valid vehicle.',
        ]);

        $vehicle = Vehicle::find($validated['selectedVehicleId']);

        if ($this->driver->role !== 'driver') {
            session()->flash('error', 'Selected user is not a driver.');

            return;
        }

        // نفس منطق الـ DriverController: معاملة واحدة تلغي التخصيصات النشطة
        // السابقة (للسائق والمركبة) وتنشئ تخصيص جديد
        DB::transaction(function () use ($vehicle) {
            DriverVehicleAssignment::where('driver_id', $this->driver->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'unassigned_at' => now()]);

            DriverVehicleAssignment::where('vehicle_id', $vehicle->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'unassigned_at' => now()]);

            DriverVehicleAssignment::create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $vehicle->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        });

        $this->driver = $this->driver->fresh([
            'driverProfile',
            'vehicleAssignments' => function ($query) {
                $query->latest('assigned_at')
                    ->with('vehicle:id,plate_number,model,type');
            },
        ]);

        $this->selectedVehicleId = 0;

        session()->flash('success', 'Driver assigned to vehicle successfully.');
    }

    public function unassignVehicle(): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to manage assignments.');

            return;
        }

        $updated = DriverVehicleAssignment::where('driver_id', $this->driver->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'unassigned_at' => now()]);

        if (! $updated) {
            session()->flash('error', 'No active assignment found for this driver.');

            return;
        }

        $this->driver = $this->driver->fresh([
            'driverProfile',
            'vehicleAssignments' => function ($query) {
                $query->latest('assigned_at')
                    ->with('vehicle:id,plate_number,model,type');
            },
        ]);

        session()->flash('success', 'Driver unassigned successfully.');
    }

    public function updateStatus(): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to update driver status.');

            return;
        }

        $validated = $this->validate([
            'selectedStatus' => ['required', 'in:available,on_duty,suspended'],
        ], [
            'selectedStatus.required' => 'Please select a status.',
            'selectedStatus.in' => 'Invalid status selected.',
        ]);

        if (! $this->driver->driverProfile) {
            session()->flash('error', 'User is not a driver.');

            return;
        }

        $this->driver->driverProfile->update(['status' => $validated['selectedStatus']]);

        $this->driver = $this->driver->fresh([
            'driverProfile',
            'vehicleAssignments' => function ($query) {
                $query->latest('assigned_at')
                    ->with('vehicle:id,plate_number,model,type');
            },
        ]);

        session()->flash('success', 'Driver status updated successfully.');
    }
};
?>

<div class="space-y-6">

    {{-- ============================================================ --}}
    {{-- Header --}}
    {{-- ============================================================ --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-center gap-4">

            <div
                class="flex h-14 w-14
                       shrink-0 items-center
                       justify-center
                       rounded-2xl bg-blue-100
                       text-xl font-bold
                       text-blue-700"
            >
                {{ strtoupper(substr($this->driver->name, 0, 1)) }}
            </div>

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $this->driver->name }}
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Driver profile and vehicle assignments.
                </p>

            </div>

        </div>


        <a
            href="/drivers"
            wire:navigate
            class="inline-flex items-center
                   justify-center rounded-xl border
                   border-slate-200 bg-white
                   px-5 py-3 text-sm
                   font-semibold text-slate-700
                   transition hover:bg-slate-50"
        >
            ← Back to Drivers
        </a>

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
    {{-- Profile Information --}}
    {{-- ============================================================ --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-slate-900">
            Profile Information
        </h2>

        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Phone
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->driver->phone ?: '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Email
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->driver->email ?: '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Status
                </p>

                <p class="mt-1">
                    @php
                        $statusClasses = match ($this->driver->driverProfile?->status) {
                            'available' => 'bg-emerald-100 text-emerald-700',
                            'on_duty' => 'bg-blue-100 text-blue-700',
                            'suspended' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $statusLabel = match ($this->driver->driverProfile?->status) {
                            'on_duty' => 'On Duty',
                            default => ucfirst($this->driver->driverProfile?->status ?? 'No profile'),
                        };
                    @endphp

                    <span
                        class="inline-flex rounded-full
                               px-3 py-1 text-xs
                               font-semibold {{ $statusClasses }}"
                    >
                        {{ $statusLabel }}
                    </span>
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Rating
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    @if ($this->driver->driverProfile)
                        {{ number_format((float) $this->driver->driverProfile->rating_avg, 2) }}
                        <span class="text-xs font-normal text-slate-400">/ 5</span>
                    @else
                        —
                    @endif
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    License Number
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->driver->driverProfile?->license_number ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    License Expiry
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->driver->driverProfile?->license_expiry?->format('M j, Y') ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    National ID
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->driver->driverProfile?->national_id ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Joined
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $this->driver->driverProfile?->joined_at?->format('M j, Y') ?? '—' }}
                </p>
            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- Current Assignment --}}
    {{-- ============================================================ --}}

    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="border-b border-slate-200 px-6 py-5">

            <h2 class="text-lg font-semibold text-slate-900">
                Current Vehicle Assignment
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                The vehicle currently assigned to this driver.
            </p>

        </div>

        <div class="p-6">

            @if ($this->currentAssignment?->vehicle)

                <div class="flex items-center gap-4">

                    <div
                        class="flex h-12 w-12
                               shrink-0 items-center
                               justify-center
                               rounded-xl bg-violet-100
                               text-xl"
                    >
                        🚚
                    </div>

                    <div>

                        <p class="font-semibold text-slate-900">
                            {{ $this->currentAssignment->vehicle->plate_number }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $this->currentAssignment->vehicle->model }}

                            ·
                            {{ ucfirst(str_replace('_', ' ', $this->currentAssignment->vehicle->type ?? '')) }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Assigned since
                            {{ $this->currentAssignment->assigned_at->format('M j, Y') }}
                        </p>

                    </div>

                </div>

            @else

                <div
                    class="rounded-xl border border-slate-200
                           bg-slate-50 px-4 py-5"
                >

                    <p class="text-sm font-medium text-slate-700">
                        No vehicle assigned
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        This driver is currently not assigned to any vehicle.
                    </p>

                </div>

            @endif


            {{-- Assignment controls (admin/dispatcher only) --}}

            @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

                <div class="mt-6 border-t border-slate-200 pt-6">

                    <form wire:submit="assignVehicle"
                          class="flex flex-col gap-3 sm:flex-row sm:items-end"
                    >

                        <div class="flex-1">

                            <label
                                for="vehicle-select"
                                class="mb-2 block text-sm font-medium
                                       text-slate-700"
                            >
                                Assign Vehicle
                            </label>

                            <select
                                id="vehicle-select"
                                wire:model="selectedVehicleId"
                                class="w-full rounded-xl border
                                       border-slate-300 bg-white
                                       px-4 py-3
                                       outline-none transition
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
                            >

                                <option value="0">
                                    Select a vehicle...
                                </option>

                                @foreach ($this->availableVehicles as $vehicle)

                                    <option value="{{ $vehicle->id }}">
                                        {{ $vehicle->plate_number }} — {{ $vehicle->model }}

                                        @if ($vehicle->currentAssignment?->driver)
                                            (assigned to {{ $vehicle->currentAssignment->driver->name }})
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                            @error('selectedVehicleId')

                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center
                                   justify-center rounded-xl
                                   bg-blue-600 px-5 py-3
                                   text-sm font-semibold
                                   text-white transition
                                   hover:bg-blue-700"
                        >
                            Assign
                        </button>

                    </form>


                    @if ($this->currentAssignment)

                        <div class="mt-4">

                            <button
                                type="button"
                                wire:click="unassignVehicle"
                                wire:confirm="Are you sure you want to unassign this driver from the current vehicle?"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center
                                       justify-center rounded-xl border
                                       border-red-200 bg-white
                                       px-5 py-3 text-sm
                                       font-semibold text-red-600
                                       transition hover:bg-red-50"
                            >
                                Unassign Current Vehicle
                            </button>

                        </div>

                    @endif

                </div>

            @endif

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- Status Management (admin/dispatcher only) --}}
    {{-- ============================================================ --}}

    @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

        <div class="rounded-2xl border border-slate-200 bg-white">

            <div class="border-b border-slate-200 px-6 py-5">

                <h2 class="text-lg font-semibold text-slate-900">
                    Driver Status
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Update the driver availability status.
                </p>

            </div>

            <div class="p-6">

                <form wire:submit="updateStatus"
                      class="flex flex-col gap-3 sm:flex-row sm:items-end"
                >

                    <div class="flex-1">

                        <label
                            for="status-select"
                            class="mb-2 block text-sm font-medium
                                   text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            id="status-select"
                            wire:model="selectedStatus"
                            class="w-full rounded-xl border
                                   border-slate-300 bg-white
                                   px-4 py-3
                                   outline-none transition
                                   focus:border-blue-500
                                   focus:ring-4
                                   focus:ring-blue-500/10"
                        >

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

                        @error('selectedStatus')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center
                               justify-center rounded-xl
                               bg-blue-600 px-5 py-3
                               text-sm font-semibold
                               text-white transition
                               hover:bg-blue-700"
                    >
                        Update Status
                    </button>

                </form>

            </div>

        </div>

    @endif


    {{-- ============================================================ --}}
    {{-- Assignment History --}}
    {{-- ============================================================ --}}

    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="border-b border-slate-200 px-6 py-5">

            <h2 class="text-lg font-semibold text-slate-900">
                Assignment History
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                All vehicle assignments for this driver.
            </p>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider
                               text-slate-500"
                    >

                        <th class="px-6 py-4">
                            Vehicle
                        </th>

                        <th class="px-6 py-4">
                            Assigned At
                        </th>

                        <th class="px-6 py-4">
                            Unassigned At
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->assignmentHistory as $assignment)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Vehicle --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if ($assignment->vehicle)

                                    <a
                                        href="/vehicles/{{ $assignment->vehicle->id }}"
                                        wire:navigate
                                        class="font-semibold text-blue-600
                                               hover:text-blue-700"
                                    >
                                        {{ $assignment->vehicle->plate_number }}
                                    </a>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $assignment->vehicle->model }}
                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        Deleted vehicle
                                    </span>

                                @endif

                            </td>

                            {{-- Assigned At --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <span class="text-sm text-slate-600">
                                    {{ $assignment->assigned_at->format('M j, Y g:i A') }}
                                </span>

                            </td>

                            {{-- Unassigned At --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if ($assignment->unassigned_at)

                                    <span class="text-sm text-slate-600">
                                        {{ $assignment->unassigned_at->format('M j, Y g:i A') }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>

                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if ($assignment->is_active)

                                    <span
                                        class="inline-flex rounded-full
                                               bg-emerald-100 px-3 py-1
                                               text-xs font-semibold
                                               text-emerald-700"
                                    >
                                        Active
                                    </span>

                                @else

                                    <span
                                        class="inline-flex rounded-full
                                               bg-slate-100 px-3 py-1
                                               text-xs font-medium
                                               text-slate-600"
                                    >
                                        Ended
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="4" class="px-6 py-12 text-center">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No assignments
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    This driver has never been assigned to a vehicle.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

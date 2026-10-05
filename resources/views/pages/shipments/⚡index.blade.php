<?php

use App\Models\Shipment;
use Livewire\WithPagination;
use Livewire\Component;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public int $perPage = 15;

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

    public function getShipmentsProperty()
    {
        return Shipment::query()
            ->visibleTo(auth()->user())
            ->with([
                'customer:id,name,phone',
                'vehicle:id,plate_number,model,type',
            ])
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('tracking_number', 'like', $search)
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function deleteShipment(int $shipmentId): void
    {
        $shipment = Shipment::findOrFail($shipmentId);

        $this->authorize('delete', $shipment);

        if ($shipment->status !== 'pending') {
            session()->flash(
                'error',
                'Only pending shipments can be deleted.'
            );

            return;
        }

        $shipment->delete();

        session()->flash(
            'success',
            'Shipment deleted successfully.'
        );
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
                Shipments
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage and track all shipments.
            </p>
        </div>

       @if (in_array(auth()->user()->role, ['admin', 'dispatcher', 'customer'], true))

    <a
        href="/shipments/create"
        wire:navigate
        class="inline-flex items-center justify-center gap-2
               rounded-xl bg-blue-600 px-5 py-3
               text-sm font-semibold text-white
               transition hover:bg-blue-700"
    >
        <span class="text-lg leading-none">+</span>

        Create Shipment
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
                    for="search"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Search
                </label>

                <div class="relative">

                    <span
                        class="pointer-events-none absolute
                               left-4 top-1/2
                               -translate-y-1/2
                               text-slate-400"
                    >
                        🔍
                    </span>

                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by tracking number, customer name or phone..."
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
                    for="status"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Status
                </label>

                <select
                    id="status"
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

                    <option value="pending">
                        Pending
                    </option>

                    <option value="assigned">
                        Assigned
                    </option>

                    <option value="picked_up">
                        Picked Up
                    </option>

                    <option value="delivered">
                        Delivered
                    </option>

                    <option value="cancelled">
                        Cancelled
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
                    class="text-sm font-medium
                           text-blue-600
                           hover:text-blue-700"
                >
                    Clear filters
                </button>

            </div>

        @endif

    </div>


    {{-- ============================================================ --}}
    {{-- Shipments Table --}}
    {{-- ============================================================ --}}

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
                            Tracking
                        </th>

                        <th
                            class="px-6 py-4 text-left
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Customer
                        </th>

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
                            Status
                        </th>

                        <th
                            class="px-6 py-4 text-left
                                   text-xs font-semibold
                                   uppercase tracking-wider
                                   text-slate-500"
                        >
                            Amount
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


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->shipments as $shipment)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Tracking --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <div class="font-semibold text-slate-900">
                                    {{ $shipment->tracking_number }}
                                </div>

                                <div class="mt-1 text-xs text-slate-400">
                                    #{{ $shipment->id }}
                                </div>

                            </td>


                            {{-- Customer --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($shipment->customer)

                                    <div class="font-medium text-slate-900">
                                        {{ $shipment->customer->name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $shipment->customer->phone }}
                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        No customer
                                    </span>

                                @endif

                            </td>


                            {{-- Vehicle --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($shipment->vehicle)

                                    <div class="font-medium text-slate-900">
                                        {{ $shipment->vehicle->plate_number }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $shipment->vehicle->model }}
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


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $statusClasses = match ($shipment->status) {
                                        'pending' =>
                                            'bg-amber-100 text-amber-700',

                                        'assigned' =>
                                            'bg-blue-100 text-blue-700',

                                        'picked_up' =>
                                            'bg-purple-100 text-purple-700',

                                        'delivered' =>
                                            'bg-emerald-100 text-emerald-700',

                                        'cancelled' =>
                                            'bg-red-100 text-red-700',

                                        default =>
                                            'bg-slate-100 text-slate-700',
                                    };

                                    $statusLabel = match ($shipment->status) {
                                        'picked_up' => 'Picked Up',
                                        default => ucfirst($shipment->status),
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


                            {{-- Amount --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <span class="font-semibold text-slate-900">
                                    ${{ number_format((float) $shipment->total_amount, 2) }}
                                </span>

                            </td>


                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <div class="flex items-center justify-end gap-2">

                                    {{-- View --}}
                                    <a
                                        href="/shipments/{{ $shipment->id }}"
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


                                    {{-- Delete --}}
                                    @if ($shipment->status === 'pending')

                                        <button
                                            type="button"
                                            wire:click="deleteShipment({{ $shipment->id }})"
                                            wire:confirm="Are you sure you want to delete this shipment?"
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

                            <td
                                colspan="6"
                                class="px-6 py-16 text-center"
                            >

                                <div class="text-4xl">
                                    📦
                                </div>

                                <h3
                                    class="mt-4 text-lg
                                           font-semibold
                                           text-slate-900"
                                >
                                    No shipments found
                                </h3>

                                <p
                                    class="mt-1 text-sm
                                           text-slate-500"
                                >
                                    Try changing your search or filters.
                                </p>

                                @if ($search || $status)

                                    <button
                                        type="button"
                                        wire:click="clearFilters"
                                        class="mt-4 text-sm
                                               font-semibold
                                               text-blue-600
                                               hover:text-blue-700"
                                    >
                                        Clear filters
                                    </button>

                                @endif

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ======================================================== --}}
        {{-- Pagination --}}
        {{-- ======================================================== --}}

        @if ($this->shipments->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->shipments->links() }}

            </div>

        @endif

    </div>

</div>
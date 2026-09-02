<?php

use App\Models\Shipment;
use Livewire\Component;

new class extends Component
{
    public Shipment $shipment;

    public function mount(Shipment $shipment): void
    {
        $this->shipment = $shipment->load([
            'customer:id,name,phone,email',
            'vehicle',
            'items',
            'statusHistory',
            'payment',
            'rating',
        ]);
    }

    public function getStatusLabelProperty(): string
    {
        return match ($this->shipment->status) {
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'picked_up' => 'Picked Up',
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
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

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
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Shipment Details
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $shipment->tracking_number }}
                </p>
            </div>

        </div>

        <span
            class="inline-flex w-fit rounded-full px-3 py-1.5 text-sm font-semibold
                   {{ $this->statusClasses }}"
        >
            {{ $this->statusLabel }}
        </span>

    </div>


    {{-- Shipment Information --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

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

    </div>


    {{-- Customer --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-slate-900">
            Customer
        </h2>

        @if ($shipment->customer)

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Name
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        {{ $shipment->customer->name }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Phone
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        {{ $shipment->customer->phone }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Email
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        {{ $shipment->customer->email ?? '—' }}
                    </p>
                </div>

            </div>

        @else

            <p class="mt-4 text-sm text-slate-500">
                No customer information available.
            </p>

        @endif

    </div>


    {{-- Locations --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Pickup --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Pickup Location
            </h2>

            <div class="mt-5 rounded-xl bg-slate-50 p-4">

                <p class="text-sm text-slate-500">
                    Coordinates
                </p>

                <p class="mt-2 font-mono text-sm text-slate-800">
                    {{ $shipment->pickup_location ?? '—' }}
                </p>

            </div>

        </div>


        {{-- Dropoff --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Dropoff Location
            </h2>

            <div class="mt-5 rounded-xl bg-slate-50 p-4">

                <p class="text-sm text-slate-500">
                    Coordinates
                </p>

                <p class="mt-2 font-mono text-sm text-slate-800">
                    {{ $shipment->dropoff_location ?? '—' }}
                </p>

            </div>

        </div>

    </div>


    {{-- Vehicle --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-slate-900">
            Vehicle
        </h2>

        @if ($shipment->vehicle)

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Plate Number
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $shipment->vehicle->plate_number }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Model
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $shipment->vehicle->model }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Type
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $shipment->vehicle->type }}
                    </p>
                </div>

            </div>

        @else

            <div class="mt-5 rounded-xl bg-slate-50 p-4">

                <p class="text-sm text-slate-500">
                    No vehicle has been assigned to this shipment yet.
                </p>

            </div>

        @endif

    </div>


    {{-- Items --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Shipment Items
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $shipment->items->count() }} item(s)
                </p>
            </div>

        </div>


        @if ($shipment->items->count())

            <div class="mt-5 overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">

                            <th class="px-4 py-3 font-medium">
                                Description
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Weight
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Dimensions
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Quantity
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Fragile
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($shipment->items as $item)

                            <tr class="border-b border-slate-100 last:border-0">

                                <td class="px-4 py-4 font-medium text-slate-900">
                                    {{ $item->description }}
                                </td>

                                <td class="px-4 py-4 text-slate-600">
                                    {{ $item->weight_kg !== null ? $item->weight_kg . ' kg' : '—' }}
                                </td>

                                <td class="px-4 py-4 text-slate-600">
                                    {{ $item->dimensions ?? '—' }}
                                </td>

                                <td class="px-4 py-4 text-slate-600">
                                    {{ $item->quantity }}
                                </td>

                                <td class="px-4 py-4">

                                    @if ($item->fragile)

                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            Yes
                                        </span>

                                    @else

                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
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
    <div class="rounded-2xl border border-slate-200 bg-white p-6">

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
{{ $history->created_at ? \Illuminate\Support\Carbon::parse($history->created_at)->format('Y-m-d H:i') : '—' }}                                </p>

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


    {{-- Payment & Rating --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Payment --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">

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
        <div class="rounded-2xl border border-slate-200 bg-white p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Rating
            </h2>

            @if ($shipment->rating)

                <div class="mt-5">

                    <p class="text-2xl font-bold text-slate-900">
                        {{ $shipment->rating->rating ?? '—' }}
                        <span class="text-base font-normal text-slate-400">
                            / 5
                        </span>
                    </p>

                    @if ($shipment->rating->comment ?? null)

                        <p class="mt-2 text-sm text-slate-600">
                            {{ $shipment->rating->comment }}
                        </p>

                    @endif

                </div>

            @else

                <p class="mt-5 text-sm text-slate-500">
                    No rating available.
                </p>

            @endif

        </div>

    </div>


    {{-- Footer --}}
    <div class="flex justify-end">

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
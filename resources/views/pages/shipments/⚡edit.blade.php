<?php

use App\Models\Shipment;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public Shipment $shipment;

    public string $customer_id = '';

    public string $pickup_latitude = '';

    public string $pickup_longitude = '';

    public string $dropoff_latitude = '';

    public string $dropoff_longitude = '';

    public string $estimated_arrival = '';

    public string $total_amount = '';

    public array $items = [];


    public function mount(Shipment $shipment): void
    {
        $this->shipment = $shipment->load('items');

        $this->customer_id = (string) $shipment->customer_id;

        $this->total_amount = (string) ($shipment->total_amount ?? '');

        $this->estimated_arrival = $shipment->estimated_arrival
            ? $shipment->estimated_arrival->format('Y-m-d\TH:i')
            : '';

        $pickup = $shipment->pickup_location;

if ($pickup) {
    $this->pickup_latitude = (string) $pickup->getLatitude();
    $this->pickup_longitude = (string) $pickup->getLongitude();
}

$dropoff = $shipment->dropoff_location;

if ($dropoff) {
    $this->dropoff_latitude = (string) $dropoff->getLatitude();
    $this->dropoff_longitude = (string) $dropoff->getLongitude();
}

        $this->items = $shipment->items
            ->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description ?? '',
                'weight_kg' => $item->weight_kg !== null
                    ? (string) $item->weight_kg
                    : '',
                'dimensions' => $item->dimensions ?? '',
                'quantity' => (int) $item->quantity,
                'fragile' => (bool) $item->fragile,
            ])
            ->values()
            ->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }
    }


    public function getCustomersProperty()
    {
        return User::query()
            ->where('role', 'customer')
            ->orderBy('name')
            ->get();
    }


    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'description' => '',
            'weight_kg' => '',
            'dimensions' => '',
            'quantity' => 1,
            'fragile' => false,
        ];
    }


    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);

        $this->items = array_values($this->items);
    }


    public function update(): void
    {
        $validated = $this->validate([
            'customer_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'pickup_latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'pickup_longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'dropoff_latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'dropoff_longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'estimated_arrival' => [
                'nullable',
                'date',
            ],

            'total_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.description' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.weight_kg' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999.99',
            ],

            'items.*.dimensions' => [
                'nullable',
                'string',
                'max:50',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.fragile' => [
                'boolean',
            ],
        ]);


        $customer = User::query()
            ->where('id', $validated['customer_id'])
            ->where('role', 'customer')
            ->first();

        if (! $customer) {
            $this->addError(
                'customer_id',
                'The selected user is not a customer.'
            );

            return;
        }


        DB::transaction(function () use ($validated) {

            $this->shipment->update([
                'customer_id' => $validated['customer_id'],

                'pickup_location' => Point::makeGeodetic(
                    (float) $validated['pickup_latitude'],
                    (float) $validated['pickup_longitude']
                ),

                'dropoff_location' => Point::makeGeodetic(
                    (float) $validated['dropoff_latitude'],
                    (float) $validated['dropoff_longitude']
                ),

                'estimated_arrival' =>
                    $validated['estimated_arrival'] ?: null,

                'total_amount' =>
                    $validated['total_amount'],
            ]);


            $existingItemIds = [];

            foreach ($validated['items'] as $item) {

                $itemData = [
                    'description' => $item['description'],

                    'weight_kg' =>
                        $item['weight_kg'] !== ''
                            ? $item['weight_kg']
                            : null,

                    'dimensions' =>
                        $item['dimensions'] !== ''
                            ? $item['dimensions']
                            : null,

                    'quantity' =>
                        $item['quantity'],

                    'fragile' =>
                        $item['fragile'] ?? false,
                ];


                if (! empty($item['id'])) {

                    $shipmentItem = $this->shipment
                        ->items()
                        ->where('id', $item['id'])
                        ->first();

                    if ($shipmentItem) {

                        $shipmentItem->update($itemData);

                        $existingItemIds[] = $shipmentItem->id;
                    }

                } else {

                    $newItem = $this->shipment
                        ->items()
                        ->create($itemData);

                    $existingItemIds[] = $newItem->id;
                }
            }


            $this->shipment
                ->items()
                ->whereNotIn('id', $existingItemIds)
                ->delete();
        });


        session()->flash(
            'success',
            'Shipment updated successfully.'
        );


        $this->redirect(
            route('shipments.show', $this->shipment),
            navigate: true
        );
    }
};
?>


<div class="space-y-6">

    {{-- Header --}}

    <div class="flex items-center gap-4">

        <a
            href="{{ route('shipments.show', $shipment) }}"
            wire:navigate
            class="flex h-10 w-10 items-center justify-center
                   rounded-xl border border-slate-200
                   bg-white text-slate-600
                   transition hover:bg-slate-50"
        >
            ←
        </a>

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                Edit Shipment
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Update shipment information and items.
            </p>

        </div>

    </div>


    {{-- Tracking Number / Status --}}

    <div
        class="rounded-2xl border border-slate-200
               bg-white p-6"
    >

        <div
            class="flex flex-col gap-4
                   sm:flex-row sm:items-center
                   sm:justify-between"
        >

            <div>

                <p class="text-sm text-slate-500">
                    Tracking Number
                </p>

                <p class="mt-1 text-lg font-bold text-slate-900">
                    {{ $shipment->tracking_number }}
                </p>

            </div>

            <span
                class="inline-flex w-fit rounded-full
                       bg-amber-100 px-3 py-1
                       text-xs font-semibold
                       text-amber-700"
            >
                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
            </span>

        </div>

    </div>


    {{-- Success --}}

    @if (session('success'))

        <div
            class="rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif


    <form
        wire:submit="update"
        class="space-y-6"
    >

        {{-- Shipment Information --}}

        <div
            class="rounded-2xl border border-slate-200
                   bg-white p-6"
        >

            <h2 class="text-lg font-semibold text-slate-900">
                Shipment Information
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Update the basic shipment information.
            </p>


            <div
                class="mt-6 grid grid-cols-1 gap-5
                       md:grid-cols-2"
            >

                {{-- Customer --}}

                <div>

                    <label
                        for="customer_id"
                        class="mb-2 block text-sm font-medium
                               text-slate-700"
                    >
                        Customer
                    </label>

                    <select
                        id="customer_id"
                        wire:model="customer_id"
                        class="w-full rounded-xl border
                               border-slate-300 bg-white
                               px-4 py-3 outline-none
                               focus:border-blue-500
                               focus:ring-4
                               focus:ring-blue-500/10"
                    >

                        <option value="">
                            Select customer
                        </option>

                        @foreach ($this->customers as $customer)

                            <option value="{{ $customer->id }}">
                                {{ $customer->name }}
                                — {{ $customer->phone }}
                            </option>

                        @endforeach

                    </select>

                    @error('customer_id')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Amount --}}

                <div>

                    <label
                        for="total_amount"
                        class="mb-2 block text-sm font-medium
                               text-slate-700"
                    >
                        Total Amount
                    </label>

                    <div class="relative">

                        <span
                            class="absolute left-4 top-1/2
                                   -translate-y-1/2
                                   text-sm text-slate-400"
                        >
                            $
                        </span>

                        <input
                            id="total_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="total_amount"
                            class="w-full rounded-xl border
                                   border-slate-300 px-4 py-3 pl-8
                                   outline-none
                                   focus:border-blue-500
                                   focus:ring-4
                                   focus:ring-blue-500/10"
                        >

                    </div>

                    @error('total_amount')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Estimated Arrival --}}

                <div>

                    <label
                        for="estimated_arrival"
                        class="mb-2 block text-sm font-medium
                               text-slate-700"
                    >
                        Estimated Arrival
                    </label>

                    <input
                        id="estimated_arrival"
                        type="datetime-local"
                        wire:model="estimated_arrival"
                        class="w-full rounded-xl border
                               border-slate-300 px-4 py-3
                               outline-none
                               focus:border-blue-500
                               focus:ring-4
                               focus:ring-blue-500/10"
                    >

                    @error('estimated_arrival')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Status --}}

                <div>

                    <label
                        class="mb-2 block text-sm font-medium
                               text-slate-700"
                    >
                        Current Status
                    </label>

                    <div
                        class="flex min-h-[50px] items-center
                               rounded-xl border
                               border-slate-200
                               bg-slate-50 px-4"
                    >

                        <span class="text-sm font-semibold text-slate-700">
                            {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                        </span>

                        <span class="ml-3 text-sm text-slate-500">
                            Status is managed separately.
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- Locations --}}

        <div
            class="rounded-2xl border border-slate-200
                   bg-white p-6"
        >

            <h2 class="text-lg font-semibold text-slate-900">
                Shipment Locations
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Update pickup and dropoff coordinates.
            </p>


            <div
                class="mt-6 grid grid-cols-1 gap-6
                       lg:grid-cols-2"
            >

                {{-- Pickup --}}

                <div
                    class="rounded-xl border
                           border-slate-200
                           bg-slate-50 p-5"
                >

                    <h3 class="font-semibold text-slate-800">
                        Pickup Location
                    </h3>

                    <div
                        class="mt-4 grid grid-cols-1
                               gap-4 sm:grid-cols-2"
                    >

                        <div>

                            <label
                                class="mb-2 block text-xs
                                       font-medium text-slate-600"
                            >
                                Latitude
                            </label>

                            <input
                                type="number"
                                step="any"
                                wire:model="pickup_latitude"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500"
                            >

                            @error('pickup_latitude')
                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div>

                            <label
                                class="mb-2 block text-xs
                                       font-medium text-slate-600"
                            >
                                Longitude
                            </label>

                            <input
                                type="number"
                                step="any"
                                wire:model="pickup_longitude"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500"
                            >

                            @error('pickup_longitude')
                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- Dropoff --}}

                <div
                    class="rounded-xl border
                           border-slate-200
                           bg-slate-50 p-5"
                >

                    <h3 class="font-semibold text-slate-800">
                        Dropoff Location
                    </h3>

                    <div
                        class="mt-4 grid grid-cols-1
                               gap-4 sm:grid-cols-2"
                    >

                        <div>

                            <label
                                class="mb-2 block text-xs
                                       font-medium text-slate-600"
                            >
                                Latitude
                            </label>

                            <input
                                type="number"
                                step="any"
                                wire:model="dropoff_latitude"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500"
                            >

                            @error('dropoff_latitude')
                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div>

                            <label
                                class="mb-2 block text-xs
                                       font-medium text-slate-600"
                            >
                                Longitude
                            </label>

                            <input
                                type="number"
                                step="any"
                                wire:model="dropoff_longitude"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500"
                            >

                            @error('dropoff_longitude')
                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Items --}}

        <div
            class="rounded-2xl border border-slate-200
                   bg-white p-6"
        >

            <div
                class="flex flex-col gap-4
                       sm:flex-row sm:items-center
                       sm:justify-between"
            >

                <div>

                    <h2 class="text-lg font-semibold text-slate-900">
                        Shipment Items
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update the items included in this shipment.
                    </p>

                </div>


                <button
                    type="button"
                    wire:click="addItem"
                    class="rounded-xl bg-slate-900
                           px-4 py-2.5 text-sm
                           font-semibold text-white
                           hover:bg-slate-800"
                >
                    + Add Item
                </button>

            </div>


            <div class="mt-6 space-y-4">

                @foreach ($items as $index => $item)

                    <div
                        wire:key="edit-shipment-item-{{ $item['id'] ?? 'new' }}-{{ $index }}"
                        class="rounded-xl border
                               border-slate-200 p-5"
                    >

                        <div
                            class="flex items-center
                                   justify-between"
                        >

                            <h3 class="font-semibold text-slate-800">
                                Item #{{ $index + 1 }}
                            </h3>

                            @if (count($items) > 1)

                                <button
                                    type="button"
                                    wire:click="removeItem({{ $index }})"
                                    class="text-sm font-medium
                                           text-red-600 hover:text-red-700"
                                >
                                    Remove
                                </button>

                            @endif

                        </div>


                        <div
                            class="mt-5 grid grid-cols-1
                                   gap-4 md:grid-cols-2
                                   xl:grid-cols-4"
                        >

                            {{-- Description --}}

                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium text-slate-600"
                                >
                                    Description
                                </label>

                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.description"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500"
                                >

                                @error("items.$index.description")
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- Weight --}}

                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium text-slate-600"
                                >
                                    Weight (kg)
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model="items.{{ $index }}.weight_kg"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500"
                                >

                                @error("items.$index.weight_kg")
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- Dimensions --}}

                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium text-slate-600"
                                >
                                    Dimensions
                                </label>

                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.dimensions"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500"
                                >

                            </div>


                            {{-- Quantity --}}

                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium text-slate-600"
                                >
                                    Quantity
                                </label>

                                <input
                                    type="number"
                                    min="1"
                                    wire:model="items.{{ $index }}.quantity"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500"
                                >

                            </div>

                        </div>


                        {{-- Fragile --}}

                        <label
                            class="mt-4 inline-flex
                                   cursor-pointer items-center gap-2"
                        >

                            <input
                                type="checkbox"
                                wire:model="items.{{ $index }}.fragile"
                                class="rounded border-slate-300"
                            >

                            <span class="text-sm text-slate-700">
                                Fragile item
                            </span>

                        </label>

                    </div>

                @endforeach

            </div>

        </div>


        {{-- Actions --}}

        <div
            class="flex flex-col-reverse gap-3
                   sm:flex-row sm:justify-end"
        >

            <a
                href="{{ route('shipments.show', $shipment) }}"
                wire:navigate
                class="inline-flex items-center
                       justify-center rounded-xl
                       border border-slate-300
                       px-5 py-3
                       text-sm font-semibold
                       text-slate-700
                       hover:bg-slate-50"
            >
                Cancel
            </a>


            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="update"
                class="inline-flex items-center
                       justify-center rounded-xl
                       bg-blue-600 px-6 py-3
                       text-sm font-semibold
                       text-white
                       hover:bg-blue-700
                       disabled:cursor-not-allowed
                       disabled:opacity-60"
            >

                <span wire:loading.remove wire:target="update">
                    Save Changes
                </span>

                <span wire:loading wire:target="update">
                    Saving...
                </span>

            </button>

        </div>

    </form>

</div>
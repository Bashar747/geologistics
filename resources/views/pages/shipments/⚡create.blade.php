<?php

use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\User;
use App\Services\ShipmentPricingService;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    /*
    |--------------------------------------------------------------------------
    | Shipment Information
    |--------------------------------------------------------------------------
    */

    public function mount(): void
{
    $this->authorize('create', Shipment::class);
}

    public string $customer_id = '';

    public string $vehicle_id = '';

    public string $pickup_latitude = '';

    public string $pickup_longitude = '';

    public string $dropoff_latitude = '';

    public string $dropoff_longitude = '';

    public string $estimated_arrival = '';

    public string $total_amount = '';


    /*
    |--------------------------------------------------------------------------
    | Shipment Items
    |--------------------------------------------------------------------------
    */

    public array $items = [
        [
            'description' => '',
            'weight_kg' => '',
            'dimensions' => '',
            'quantity' => 1,
            'fragile' => false,
        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    public function getCustomersProperty()
    {
         $user = auth()->user();

    if ($user->role === 'customer') {
        return User::where('id', $user->id)->get();
    }

    return User::query()
        ->where('role', 'customer')
        ->orderBy('name')
        ->get();
    }

    public function getVehiclesProperty()
    {
        return \App\Models\Vehicle::query()
            ->orderBy('plate_number')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Add Item
    |--------------------------------------------------------------------------
    */

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'weight_kg' => '',
            'dimensions' => '',
            'quantity' => 1,
            'fragile' => false,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Item
    |--------------------------------------------------------------------------
    */

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);

        $this->items = array_values($this->items);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Shipment
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {

        $this->authorize('create', Shipment::class);
        $validated = $this->validate([

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'vehicle_id' => [
                'nullable',
                'integer',
                'exists:vehicles,id',
            ],


            /*
            |--------------------------------------------------------------------------
            | Pickup Location
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Dropoff Location
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Shipment Details
            |--------------------------------------------------------------------------
            */

          'estimated_arrival' => [
             'nullable',
              'date',
               ],

             'total_amount' => [
                 'nullable',
                  'numeric',
                  'min:0',
                ],


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

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


        /*
        |--------------------------------------------------------------------------
        | Make Sure Selected User Is Customer
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Create Shipment
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($validated) {


        $initialStatus = $this->vehicle_id !== ''
    ? 'assigned'
    : 'pending';
            $shipment = Shipment::create([

                /*
                |--------------------------------------------------------------------------
                | Tracking Number
                |--------------------------------------------------------------------------
                */

                'tracking_number' => $this->generateTrackingNumber(),


                /*
                |--------------------------------------------------------------------------
                | Customer
                |--------------------------------------------------------------------------
                */

                'customer_id' => $validated['customer_id'],


               

                'vehicle_id' => $this->vehicle_id !== '' ? $this->vehicle_id : null,

               'status' => $initialStatus,


                /*
                |--------------------------------------------------------------------------
                | Pickup Location
                |--------------------------------------------------------------------------
                |
                | PostGIS Geography POINT - SRID 4326
                |
                */

                'pickup_location' => Point::makeGeodetic(
                    (float) $validated['pickup_latitude'],
                    (float) $validated['pickup_longitude']
                ),


                /*
                |--------------------------------------------------------------------------
                | Dropoff Location
                |--------------------------------------------------------------------------
                */

                'dropoff_location' => Point::makeGeodetic(
                    (float) $validated['dropoff_latitude'],
                    (float) $validated['dropoff_longitude']
                ),


                /*
                |--------------------------------------------------------------------------
                | Initial Status
                |--------------------------------------------------------------------------
                */

                


                /*
                |--------------------------------------------------------------------------
                | Other Information
                |--------------------------------------------------------------------------
                */

               'estimated_arrival' => null,

'total_amount' => null,
            ]);

              app(ShipmentPricingService::class)->apply($shipment);
            /*
            |--------------------------------------------------------------------------
            | Create Shipment Items
            |--------------------------------------------------------------------------
            */

            foreach ($validated['items'] as $item) {

                $shipment->items()->create([

                    'description' =>
                        $item['description'],

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
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Initial Status History
            |--------------------------------------------------------------------------
            */

            ShipmentStatusHistory::create([

                'shipment_id' =>
                    $shipment->id,

                'status' =>
                    $shipment->status,

                'changed_by' =>
                    auth()->id(),

                'note' =>
                    $this->vehicle_id !== '' ? 'Shipment created and assigned to vehicle.' : 'Shipment created.',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Success Message
        |--------------------------------------------------------------------------
        */

        session()->flash(
            'success',
            'Shipment created successfully.'
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

       $this->redirect('/shipments', navigate: true);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Unique Tracking Number
    |--------------------------------------------------------------------------
    */

    private function generateTrackingNumber(): string
    {
        do {

            $trackingNumber =
                'GL-' . strtoupper(Str::random(10));

        } while (
            Shipment::where(
                'tracking_number',
                $trackingNumber
            )->exists()
        );


        return $trackingNumber;
    }
};
?>


<div class="space-y-6">

    {{-- ============================================================= --}}
    {{-- Header --}}
    {{-- ============================================================= --}}

    <div class="flex items-center gap-4">

        <a
            href="/shipments"
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
                Create Shipment
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Create a new shipment and add its items.
            </p>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- Success Message --}}
    {{-- ============================================================= --}}

    @if (session('success'))

        <div
            class="rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ============================================================= --}}
    {{-- Form --}}
    {{-- ============================================================= --}}

    <form
        wire:submit="save"
        class="space-y-6"
    >


        {{-- ========================================================= --}}
        {{-- Shipment Information --}}
        {{-- ========================================================= --}}

        <div
            class="rounded-2xl border border-slate-200
                   bg-white p-6"
        >

            <div>

                <h2 class="text-lg font-semibold text-slate-900">
                    Shipment Information
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Basic information about this shipment.
                </p>

            </div>


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
                               px-4 py-3
                               outline-none transition
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


                {{-- Vehicle --}}
                <div>

                    <label
                        for="vehicle_id"
                        class="mb-2 block text-sm font-medium
                               text-slate-700"
                    >
                        Vehicle (Optional)
                    </label>

                    <select
                        id="vehicle_id"
                        wire:model="vehicle_id"
                        class="w-full rounded-xl border
                               border-slate-300 bg-white
                               px-4 py-3
                               outline-none transition
                               focus:border-blue-500
                               focus:ring-4
                               focus:ring-blue-500/10"
                    >

                        <option value="">
                            Unassigned (Pending)
                        </option>

                        @foreach ($this->vehicles as $vehicle)

                            <option value="{{ $vehicle->id }}">
                                {{ $vehicle->plate_number }} — {{ $vehicle->model }} ({{ $vehicle->type }})
                            </option>

                        @endforeach

                    </select>

                    @error('vehicle_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


               {{-- Total Amount --}}
<div>

    <label
        for="total_amount"
        class="mb-2 block text-sm font-medium text-slate-700"
    >
        Total Amount
    </label>

    <div class="relative">

        <span
            class="absolute left-4 top-1/2
                   -translate-y-1/2
                   text-sm text-slate-400"
        >
            {{ \App\Models\Setting::getValue('currency', 'USD') }}
        </span>

        <input
            id="total_amount"
            type="number"
            step="0.01"
            wire:model="total_amount"
            readonly
            placeholder="Calculated automatically"
            class="w-full rounded-xl border
                   border-slate-300
                   bg-slate-50
                   px-4 py-3 pl-16
                   text-slate-600
                   outline-none"
        >

    </div>

    <p class="mt-2 text-xs text-slate-500">
        Automatically calculated from the shipment distance and pricing settings.
    </p>

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
        class="mb-2 block text-sm font-medium text-slate-700"
    >
        Estimated Arrival
    </label>

    <input
        id="estimated_arrival"
        type="datetime-local"
        wire:model="estimated_arrival"
        readonly
        class="w-full rounded-xl border
               border-slate-300
               bg-slate-50
               px-4 py-3
               text-slate-600
               outline-none"
    >

    <p class="mt-2 text-xs text-slate-500">
        Automatically calculated using the configured average speed.
    </p>

    @error('estimated_arrival')
        <p class="mt-2 text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror

</div>

                {{-- Initial Status --}}
                <div>

                    <label
                        class="mb-2 block text-sm font-medium
                               text-slate-700"
                    >
                        Initial Status
                    </label>

                    <div
                        class="flex min-h-[50px] items-center
                               rounded-xl border
                               border-slate-200
                               bg-slate-50 px-4"
                    >

                        <span
                            class="inline-flex rounded-full
                                   bg-amber-100 px-3 py-1
                                   text-xs font-semibold
                                   text-amber-700"
                        >
                            Pending
                        </span>

                        <span class="ml-3 text-sm text-slate-500">
                            Vehicle will be assigned later.
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Locations --}}
        {{-- ========================================================= --}}

        <div
            class="rounded-2xl border border-slate-200
                   bg-white p-6"
        >

            <div>

                <h2 class="text-lg font-semibold text-slate-900">
                    Shipment Locations
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Enter latitude and longitude coordinates.
                </p>

            </div>


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

                    <p class="mt-1 text-xs text-slate-500">
                        Where the shipment will be collected.
                    </p>


                    <div
                        class="mt-4 grid grid-cols-1
                               gap-4 sm:grid-cols-2"
                    >

                        {{-- Pickup Latitude --}}
                        <div>

                            <label
                                for="pickup_latitude"
                                class="mb-2 block text-xs
                                       font-medium
                                       text-slate-600"
                            >
                                Latitude
                            </label>

                            <input
                                id="pickup_latitude"
                                type="number"
                                step="any"
                                wire:model="pickup_latitude"
                                placeholder="e.g. 33.5138"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
                            >

                            @error('pickup_latitude')

                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Pickup Longitude --}}
                        <div>

                            <label
                                for="pickup_longitude"
                                class="mb-2 block text-xs
                                       font-medium
                                       text-slate-600"
                            >
                                Longitude
                            </label>

                            <input
                                id="pickup_longitude"
                                type="number"
                                step="any"
                                wire:model="pickup_longitude"
                                placeholder="e.g. 36.2765"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
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

                    <p class="mt-1 text-xs text-slate-500">
                        Where the shipment will be delivered.
                    </p>


                    <div
                        class="mt-4 grid grid-cols-1
                               gap-4 sm:grid-cols-2"
                    >

                        {{-- Dropoff Latitude --}}
                        <div>

                            <label
                                for="dropoff_latitude"
                                class="mb-2 block text-xs
                                       font-medium
                                       text-slate-600"
                            >
                                Latitude
                            </label>

                            <input
                                id="dropoff_latitude"
                                type="number"
                                step="any"
                                wire:model="dropoff_latitude"
                                placeholder="e.g. 33.8547"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
                            >

                            @error('dropoff_latitude')

                                <p class="mt-2 text-xs text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Dropoff Longitude --}}
                        <div>

                            <label
                                for="dropoff_longitude"
                                class="mb-2 block text-xs
                                       font-medium
                                       text-slate-600"
                            >
                                Longitude
                            </label>

                            <input
                                id="dropoff_longitude"
                                type="number"
                                step="any"
                                wire:model="dropoff_longitude"
                                placeholder="e.g. 35.8623"
                                class="w-full rounded-xl border
                                       border-slate-300
                                       bg-white px-4 py-3
                                       outline-none
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
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


        {{-- ========================================================= --}}
        {{-- Shipment Items --}}
        {{-- ========================================================= --}}

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

                    <h2
                        class="text-lg font-semibold
                               text-slate-900"
                    >
                        Shipment Items
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Add all items included in the shipment.
                    </p>

                </div>


                <button
                    type="button"
                    wire:click="addItem"
                    class="inline-flex items-center
                           justify-center gap-2
                           rounded-xl bg-slate-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           transition hover:bg-slate-800"
                >

                    <span class="text-lg leading-none">
                        +
                    </span>

                    Add Item

                </button>

            </div>


            {{-- Items --}}
            <div class="mt-6 space-y-4">

                @foreach ($items as $index => $item)

                    <div
                        wire:key="shipment-item-{{ $index }}"
                        class="rounded-xl border
                               border-slate-200 p-5"
                    >

                        {{-- Item Header --}}
                        <div
                            class="flex items-center
                                   justify-between"
                        >

                            <h3
                                class="font-semibold
                                       text-slate-800"
                            >
                                Item #{{ $index + 1 }}
                            </h3>


                            @if (count($items) > 1)

                                <button
                                    type="button"
                                    wire:click="removeItem({{ $index }})"
                                    class="text-sm font-medium
                                           text-red-600
                                           transition
                                           hover:text-red-700"
                                >
                                    Remove
                                </button>

                            @endif

                        </div>


                        {{-- Item Fields --}}
                        <div
                            class="mt-5 grid grid-cols-1
                                   gap-4 md:grid-cols-2
                                   xl:grid-cols-4"
                        >

                            {{-- Description --}}
                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium
                                           text-slate-600"
                                >
                                    Description
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.description"
                                    placeholder="Package description"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500
                                           focus:ring-4
                                           focus:ring-blue-500/10"
                                >

                                @error("items.$index.description")

                                    <p
                                        class="mt-1 text-xs
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- Weight --}}
                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium
                                           text-slate-600"
                                >
                                    Weight (kg)
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model="items.{{ $index }}.weight_kg"
                                    placeholder="0.00"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500
                                           focus:ring-4
                                           focus:ring-blue-500/10"
                                >

                                @error("items.$index.weight_kg")

                                    <p
                                        class="mt-1 text-xs
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- Dimensions --}}
                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium
                                           text-slate-600"
                                >
                                    Dimensions
                                </label>

                                <input
                                    type="text"
                                    wire:model="items.{{ $index }}.dimensions"
                                    placeholder="30x20x10 cm"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500
                                           focus:ring-4
                                           focus:ring-blue-500/10"
                                >

                                @error("items.$index.dimensions")

                                    <p
                                        class="mt-1 text-xs
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- Quantity --}}
                            <div>

                                <label
                                    class="mb-2 block text-xs
                                           font-medium
                                           text-slate-600"
                                >
                                    Quantity
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="number"
                                    min="1"
                                    wire:model="items.{{ $index }}.quantity"
                                    class="w-full rounded-lg border
                                           border-slate-300
                                           px-3 py-2.5
                                           outline-none
                                           focus:border-blue-500
                                           focus:ring-4
                                           focus:ring-blue-500/10"
                                >

                                @error("items.$index.quantity")

                                    <p
                                        class="mt-1 text-xs
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>


                        {{-- Fragile --}}
                        <label
                            class="mt-4 inline-flex cursor-pointer
                                   items-center gap-2"
                        >

                            <input
                                type="checkbox"
                                wire:model="items.{{ $index }}.fragile"
                                class="rounded border-slate-300
                                       text-blue-600
                                       focus:ring-blue-500"
                            >

                            <span class="text-sm text-slate-700">
                                Fragile item
                            </span>

                        </label>

                    </div>

                @endforeach

            </div>


            @error('items')

                <p class="mt-3 text-sm text-red-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- ========================================================= --}}
        {{-- Actions --}}
        {{-- ========================================================= --}}

        <div
            class="flex flex-col-reverse gap-3
                   sm:flex-row sm:items-center
                   sm:justify-end"
        >

            {{-- Cancel --}}
            <a
                href="/shipments"
                wire:navigate
                class="inline-flex items-center
                       justify-center rounded-xl
                       border border-slate-300
                       px-5 py-3
                       text-sm font-semibold
                       text-slate-700
                       transition hover:bg-slate-50"
            >
                Cancel
            </a>


            {{-- Create --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center
                       justify-center gap-2
                       rounded-xl bg-blue-600
                       px-6 py-3
                       text-sm font-semibold
                       text-white
                       transition hover:bg-blue-700
                       disabled:cursor-not-allowed
                       disabled:opacity-60"
            >

                {{-- Normal --}}
                <span
                    wire:loading.remove
                    wire:target="save"
                >
                    Create Shipment
                </span>


                {{-- Loading --}}
                <span
                    wire:loading
                    wire:target="save"
                >
                    Creating...
                </span>

            </button>

        </div>

    </form>

</div>
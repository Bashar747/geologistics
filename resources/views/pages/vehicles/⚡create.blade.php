<?php

use App\Models\Vehicle;
use Livewire\Component;

new class extends Component
{
    public string $plate_number = '';

    public string $model = '';

    public string $type = '';

    public function save(): void
    {
        $validated = $this->validate([
            'plate_number' => [
                'required',
                'string',
                'max:50',
                'unique:vehicles,plate_number',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'type' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        Vehicle::create([
            'plate_number' => $validated['plate_number'],
            'model' => $validated['model'] ?: null,
            'type' => $validated['type'] ?: null,
            'status' => 'idle',
        ]);

        session()->flash(
            'success',
            'Vehicle created successfully.'
        );

        $this->redirectRoute(
            'vehicles.index',
            navigate: true
        );
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div>

        <div class="flex items-center gap-3">

            <a
                href="{{ route('vehicles.index') }}"
                wire:navigate
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                aria-label="Back to vehicles"
            >
                ←
            </a>

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Create Vehicle
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Add a new vehicle to your fleet.
                </p>

            </div>

        </div>

    </div>


    {{-- Form --}}
    <form
          wire:submit.prevent="save"
    class="space-y-6"

    >

        {{-- Vehicle Information --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            <div class="border-b border-slate-200 px-6 py-5">

                <h2 class="text-base font-semibold text-slate-900">
                    Vehicle Information
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Enter the basic information for this vehicle.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                {{-- Plate Number --}}
                <div>

                    <label
                        for="plate_number"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Plate Number
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="plate_number"
                        type="text"
                        wire:model="plate_number"
                        autocomplete="off"
                        placeholder="e.g. ABC-1234"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 @error('plate_number') border-red-300 focus:border-red-500 focus:ring-red-500/10 @enderror"
                    >

                    @error('plate_number')
                        <p class="mt-2 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Model --}}
                <div>

                    <label
                        for="model"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Model
                    </label>

                    <input
                        id="model"
                        type="text"
                        wire:model="model"
                        autocomplete="off"
                        placeholder="e.g. Ford Transit"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 @error('model') border-red-300 focus:border-red-500 focus:ring-red-500/10 @enderror"
                    >

                    @error('model')
                        <p class="mt-2 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Type --}}
                <div class="md:col-span-2">

                    <label
                        for="type"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Vehicle Type
                    </label>

                    <input
                        id="type"
                        type="text"
                        wire:model="type"
                        autocomplete="off"
                        placeholder="e.g. Van, Truck, Pickup"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 @error('type') border-red-300 focus:border-red-500 focus:ring-red-500/10 @enderror"
                    >

                    @error('type')
                        <p class="mt-2 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

        </div>


        {{-- Information --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">

            <div class="flex gap-3">

                <div class="mt-0.5 shrink-0 text-blue-600">
                    ⓘ
                </div>

                <div>

                    <p class="text-sm font-medium text-blue-800">
                        Initial vehicle status
                    </p>

                    <p class="mt-1 text-sm text-blue-700">
                        New vehicles are automatically created with an
                        <strong>Idle</strong> status.
                    </p>

                </div>

            </div>

        </div>


        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">

            {{-- Cancel --}}
            <a
                href="{{ route('vehicles.index') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Cancel
            </a>


            {{-- Create --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >

                <span
                    wire:loading.remove
                    wire:target="save"
                >
                    Create Vehicle
                </span>

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
<?php

use App\Models\Setting;
use Livewire\Component;

new class extends Component
{
    public string $basePrice = '';

    public string $pricePerKm = '';

    public string $currency = '';

    public string $averageSpeedKmh = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $this->basePrice = (string) Setting::getValue('base_price', 10);
        $this->pricePerKm = (string) Setting::getValue('price_per_km', 1.5);
        $this->currency = (string) Setting::getValue('currency', 'USD');
        $this->averageSpeedKmh = (string) Setting::getValue('average_speed_kmh', 50);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $this->validate([
            'basePrice' => ['required', 'numeric', 'min:0'],
            'pricePerKm' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'averageSpeedKmh' => ['required', 'numeric', 'min:1', 'max:300'],
        ]);

        Setting::setValue(
            'base_price',
            $this->basePrice,
            'decimal',
            'pricing'
        );

        Setting::setValue(
            'price_per_km',
            $this->pricePerKm,
            'decimal',
            'pricing'
        );

        Setting::setValue(
            'currency',
            strtoupper($this->currency),
            'string',
            'pricing'
        );

        Setting::setValue(
            'average_speed_kmh',
            $this->averageSpeedKmh,
            'integer',
            'eta'
        );

        session()->flash('success', 'Settings updated successfully.');
    }
};
?>

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            Settings
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Manage pricing and delivery estimation settings.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        {{-- Pricing --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">
                    Pricing
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure the shipment pricing calculation.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Base Price
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="basePrice"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3
                               outline-none focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('basePrice')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Price Per KM
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="pricePerKm"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3
                               outline-none focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('pricePerKm')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Currency
                    </label>

                    <input
                        type="text"
                        maxlength="3"
                        wire:model="currency"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3
                               uppercase outline-none focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- ETA --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">
                    ETA
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure the average vehicle speed used for ETA calculation.
                </p>
            </div>

            <div class="max-w-md">
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Average Speed (KM/H)
                </label>

                <input
                    type="number"
                    step="1"
                    min="1"
                    max="300"
                    wire:model="averageSpeedKmh"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3
                           outline-none focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                @error('averageSpeedKmh')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white
                       shadow-sm transition hover:bg-blue-700"
            >
                Save Settings
            </button>
        </div>

    </form>

</div>
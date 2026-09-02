<?php

use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Component;

new class extends Component
{
    public function getStatsProperty(): array
    {
        return [
            'shipments' => Shipment::count(),

            'pending_shipments' => Shipment::where('status', 'pending')->count(),

            'drivers' => User::where('role', 'driver')->count(),

            'vehicles' => Vehicle::count(),
        ];
    }

    public function getRecentShipmentsProperty()
    {
        return Shipment::query()
            ->with(['customer', 'vehicle'])
            ->latest()
            ->limit(5)
            ->get();
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            Dashboard
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Overview of your logistics operations.
        </p>
    </div>


    {{-- Statistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

        {{-- Shipments --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Shipments
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $this->stats['shipments'] }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-blue-50
                            flex items-center justify-center">

                    <svg
                        class="w-6 h-6 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"
                        />
                        <circle cx="7" cy="19" r="1.5"/>
                        <circle cx="18" cy="19" r="1.5"/>
                    </svg>

                </div>

            </div>

        </div>


        {{-- Pending --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Pending Shipments
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $this->stats['pending_shipments'] }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-amber-50
                            flex items-center justify-center">

                    <svg
                        class="w-6 h-6 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke-width="1.8"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-width="1.8"
                            d="M12 7v5l3 2"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Drivers --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Drivers
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $this->stats['drivers'] }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-emerald-50
                            flex items-center justify-center">

                    <svg
                        class="w-6 h-6 text-emerald-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            cx="12"
                            cy="8"
                            r="3"
                            stroke-width="1.8"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-width="1.8"
                            d="M5 20c.8-3.5 3-5 7-5s6.2 1.5 7 5"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Vehicles --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Vehicles
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $this->stats['vehicles'] }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-violet-50
                            flex items-center justify-center">

                    <svg
                        class="w-6 h-6 text-violet-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-width="1.8"
                            d="M5 17h14l-1-7H6l-1 7z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-width="1.8"
                            d="M7 10l1.5-4h7L17 10"
                        />

                        <circle cx="8" cy="18" r="1.5"/>
                        <circle cx="16" cy="18" r="1.5"/>
                    </svg>

                </div>

            </div>

        </div>

    </div>


    {{-- Recent Shipments --}}
    <div class="bg-white rounded-2xl border border-slate-200">

        <div class="px-6 py-5 border-b border-slate-200">

            <div class="flex items-center justify-between">

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Recent Shipments
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Latest shipments in the system.
                    </p>
                </div>

                <a
                    href="/shipments"
                    wire:navigate
                    class="text-sm font-semibold text-blue-600
                           hover:text-blue-700"
                >
                    View all
                </a>

            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider text-slate-500">

                        <th class="px-6 py-4">
                            Tracking Number
                        </th>

                        <th class="px-6 py-4">
                            Customer
                        </th>

                        <th class="px-6 py-4">
                            Vehicle
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                        <th class="px-6 py-4">
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->recentShipments as $shipment)

                        <tr class="hover:bg-slate-50 transition">

                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-900">
                                    {{ $shipment->tracking_number }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $shipment->customer?->name ?? '—' }}
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $shipment->vehicle?->plate_number ?? 'Unassigned' }}
                            </td>

                            <td class="px-6 py-4">

                                <span
                                    class="inline-flex px-2.5 py-1 rounded-full
                                           text-xs font-semibold bg-slate-100
                                           text-slate-700"
                                >
                                    {{ ucfirst($shipment->status) }}
                                </span>

                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                ${{ number_format((float) $shipment->total_amount, 2) }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="px-6 py-12 text-center"
                            >
                                <p class="text-sm font-medium text-slate-600">
                                    No shipments found.
                                </p>

                                <p class="text-sm text-slate-400 mt-1">
                                    Shipments will appear here once created.
                                </p>
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>
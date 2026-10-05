<?php

use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['admin', 'dispatcher'], true),
            403
        );

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getReportProperty(): array
    {
        $query = Shipment::query();

        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $totalShipments = (clone $query)->count();

        $deliveredShipments = (clone $query)
            ->where('status', 'delivered')
            ->count();

        $activeShipments = (clone $query)
            ->whereIn('status', [
                'assigned',
                'picked_up',
                'in_transit',
            ])
            ->count();

        $cancelledShipments = (clone $query)
            ->where('status', 'cancelled')
            ->count();

        $totalRevenue = (clone $query)
            ->where('status', 'delivered')
            ->sum('total_amount');

        $averageShipmentValue = $deliveredShipments > 0
            ? $totalRevenue / $deliveredShipments
            : 0;

        $statusBreakdown = (clone $query)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $vehicleStats = Vehicle::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'total_shipments' => $totalShipments,
            'delivered_shipments' => $deliveredShipments,
            'active_shipments' => $activeShipments,
            'cancelled_shipments' => $cancelledShipments,
            'total_revenue' => round((float) $totalRevenue, 2),
            'average_shipment_value' => round((float) $averageShipmentValue, 2),
            'status_breakdown' => $statusBreakdown,
            'vehicle_stats' => $vehicleStats,
        ];
    }
};
?>

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            Reports
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Shipment and fleet performance overview.
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="grid gap-4 sm:grid-cols-2">

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    From
                </label>

                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    To
                </label>

                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                >
            </div>

        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Total Shipments</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ $this->report['total_shipments'] }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Delivered</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">
                {{ $this->report['delivered_shipments'] }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Active</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">
                {{ $this->report['active_shipments'] }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Cancelled</p>
            <p class="mt-2 text-3xl font-bold text-red-600">
                {{ $this->report['cancelled_shipments'] }}
            </p>
        </div>

    </div>

    <div class="grid gap-4 md:grid-cols-2">

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">
                Total Revenue
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                ${{ number_format($this->report['total_revenue'], 2) }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">
                Average Delivered Shipment Value
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                ${{ number_format($this->report['average_shipment_value'], 2) }}
            </p>
        </div>

    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        <div class="rounded-xl border border-slate-200 bg-white p-5">

            <h2 class="text-lg font-semibold text-slate-900">
                Shipment Status
            </h2>

            <div class="mt-4 space-y-3">

                @forelse ($this->report['status_breakdown'] as $item)

                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">

                        <span class="text-sm font-medium capitalize text-slate-700">
                            {{ str_replace('_', ' ', $item['status']) }}
                        </span>

                        <span class="text-sm font-bold text-slate-900">
                            {{ $item['total'] }}
                        </span>

                    </div>

                @empty

                    <p class="text-sm text-slate-500">
                        No shipments found for this period.
                    </p>

                @endforelse

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">

            <h2 class="text-lg font-semibold text-slate-900">
                Fleet Status
            </h2>

            <div class="mt-4 space-y-3">

                @forelse ($this->report['vehicle_stats'] as $item)

                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">

                        <span class="text-sm font-medium capitalize text-slate-700">
                            {{ str_replace('_', ' ', $item['status']) }}
                        </span>

                        <span class="text-sm font-bold text-slate-900">
                            {{ $item['total'] }}
                        </span>

                    </div>

                @empty

                    <p class="text-sm text-slate-500">
                        No vehicles found.
                    </p>

                @endforelse

            </div>

        </div>

    </div>

</div>

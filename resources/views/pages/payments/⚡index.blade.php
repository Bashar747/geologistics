<?php

use App\Models\Payment;
use App\Models\Shipment;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function getPaymentsProperty()
{
    $query = Payment::query()
        ->with([
            'shipment:id,tracking_number,customer_id,total_amount',
            'shipment.customer:id,name',
        ]);

    // Customer can only see payments for their own shipments.
    if (auth()->user()->role === 'customer') {
        $query->whereHas('shipment', function ($query) {
            $query->where('customer_id', auth()->id());
        });
    }

    $query
        ->when($this->search, function ($query) {
            $search = '%' . $this->search . '%';

            $query->whereHas('shipment', function ($query) use ($search) {
                $query->where('tracking_number', 'like', $search);
            });
        })
        ->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })
        ->latest();

    return $query->paginate($this->perPage);
}
    public function confirmPayment(int $paymentId): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to confirm payments.');

            return;
        }

        $payment = Payment::findOrFail($paymentId);

        if ($payment->status === 'paid') {
            session()->flash('error', 'Payment is already confirmed.');

            return;
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        session()->flash('success', 'Payment confirmed successfully.');
    }

    public function refundPayment(int $paymentId): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to refund payments.');

            return;
        }

        $payment = Payment::findOrFail($paymentId);

        if ($payment->status !== 'paid') {
            session()->flash('error', 'Only paid payments can be refunded.');

            return;
        }

        $payment->update(['status' => 'refunded']);

        session()->flash('success', 'Payment refunded successfully.');
    }

    private function canManage(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'dispatcher'], true);
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            Payments
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Shipment payments and their statuses.
        </p>
    </div>


    {{-- Flash Messages --}}
    @if (session('success'))

        <div class="rounded-xl border border-emerald-200
                    bg-emerald-50 px-4 py-3
                    text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif

    @if (session('error'))

        <div class="rounded-xl border border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700"
        >
            {{ session('error') }}
        </div>

    @endif


    {{-- Filters --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Search --}}
            <div class="md:col-span-2">

                <label
                    for="payment-search"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Search
                </label>

                <div class="relative">

                    <span class="pointer-events-none absolute
                                 left-4 top-1/2 -translate-y-1/2
                                 text-slate-400"
                    >
                        🔍
                    </span>

                    <input
                        id="payment-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by tracking number..."
                        class="w-full rounded-xl border border-slate-300
                               px-4 py-3 pl-11
                               outline-none transition
                               focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                </div>

            </div>


            {{-- Status --}}
            <div>

                <label
                    for="payment-status"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Status
                </label>

                <select
                    id="payment-status"
                    wire:model.live="status"
                    class="w-full rounded-xl border border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                    <option value="">
                        All statuses
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="paid">
                        Paid
                    </option>

                    <option value="refunded">
                        Refunded
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


    {{-- Payments Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider text-slate-500"
                    >

                        <th class="px-6 py-4">
                            Shipment
                        </th>

                        <th class="px-6 py-4">
                            Customer
                        </th>

                        <th class="px-6 py-4">
                            Amount
                        </th>

                        <th class="px-6 py-4">
                            Method
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                        <th class="px-6 py-4">
                            Paid At
                        </th>

                       @if ($this->canManage())

    <th class="px-6 py-4 text-right">
        Actions
    </th>

@endif

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->payments as $payment)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Shipment --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($payment->shipment)

                                    <a href="/shipments/{{ $payment->shipment->id }}"
                                       wire:navigate
                                       class="font-semibold text-blue-600
                                              hover:text-blue-700"
                                    >
                                        {{ $payment->shipment->tracking_number }}
                                    </a>

                                    <div class="mt-1 text-xs text-slate-400">
                                        #{{ $payment->shipment->id }}
                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        No shipment
                                    </span>

                                @endif

                            </td>


                            {{-- Customer --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($payment->shipment?->customer)

                                    <div class="font-medium text-slate-900">
                                        {{ $payment->shipment->customer->name }}
                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Amount --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <span class="font-semibold text-slate-900">
                                    ${{ number_format((float) $payment->amount, 2) }}
                                </span>

                            </td>


                            {{-- Method --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <span class="text-sm text-slate-600">
                                    {{ ucfirst($payment->method) }}
                                </span>

                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $statusClasses = match ($payment->status) {
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'refunded' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <span class="inline-flex rounded-full
                                             px-3 py-1 text-xs
                                             font-semibold {{ $statusClasses }}"
                                >
                                    {{ ucfirst($payment->status) }}
                                </span>

                            </td>


                            {{-- Paid At --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($payment->paid_at)

                                    <span class="text-sm text-slate-600">
                                        {{ $payment->paid_at->format('M j, Y g:i A') }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                           @if ($this->canManage())
                                <td class="whitespace-nowrap px-6 py-5 text-right">

                                    <div class="inline-flex gap-2">

                                        @if ($payment->status === 'pending')

                                            <button
                                                type="button"
                                                wire:click="confirmPayment({{ $payment->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex rounded-lg
                                                       bg-emerald-600 px-3 py-1.5
                                                       text-xs font-semibold text-white
                                                       transition hover:bg-emerald-700"
                                            >
                                                Confirm
                                            </button>

                                        @endif

                                        @if ($payment->status === 'paid')

                                            <button
                                                type="button"
                                                wire:click="refundPayment({{ $payment->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex rounded-lg
                                                       bg-red-600 px-3 py-1.5
                                                       text-xs font-semibold text-white
                                                       transition hover:bg-red-700"
                                            >
                                                Refund
                                            </button>

                                        @endif

                                    </div>

                                </td>

                            @endif

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7" class="px-6 py-12 text-center">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No payments found
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


        {{-- Pagination --}}
        @if ($this->payments->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->payments->links() }}

            </div>

        @endif

    </div>

</div>

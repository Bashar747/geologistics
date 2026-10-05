<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $action = '';

    public string $entityType = '';

    public string $fromDate = '';

    public string $toDate = '';

    public int $userId = 0;

    public int $perPage = 30;

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedEntityType(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->action = '';
        $this->entityType = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->userId = 0;

        $this->resetPage();
    }

    public function getAuditLogsProperty()
    {
        // نفس فلاتر الـ AuditLogController@index بالضبط
        return AuditLog::query()
            ->with('user:id,name,role')
            ->when($this->userId, function ($query) {
                $query->where('user_id', $this->userId);
            })
            ->when($this->action, function ($query) {
                $query->where('action', 'like', '%' . $this->action . '%');
            })
            ->when($this->entityType, function ($query) {
                $query->where('entity_type', $this->entityType);
            })
            ->when($this->fromDate, function ($query) {
                $query->whereDate('created_at', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($query) {
                $query->whereDate('created_at', '<=', $this->toDate);
            })
            ->latest('created_at')
            ->paginate($this->perPage);
    }

    public function getUsersProperty()
    {
        return User::query()
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();
    }

    public function getEntityTypesProperty()
    {
        return AuditLog::query()
            ->whereNotNull('entity_type')
            ->distinct()
            ->pluck('entity_type');
    }
};
?>

<div class="space-y-6">

    {{-- ============================================================ --}}
    {{-- Header --}}
    {{-- ============================================================ --}}

    <div>

        <h1 class="text-2xl font-bold text-slate-900">
            Audit Logs
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            System activity history (read-only).
        </p>

    </div>


    {{-- ============================================================ --}}
    {{-- Filters --}}
    {{-- ============================================================ --}}

    <div
        class="rounded-2xl border border-slate-200
               bg-white p-5"
    >

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- User --}}
            <div>

                <label
                    for="audit-user"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    User
                </label>

                <select
                    id="audit-user"
                    wire:model.live="userId"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

                    <option value="0">
                        All users
                    </option>

                    @foreach ($this->users as $user)

                        <option value="{{ $user->id }}">
                            {{ $user->name }}
                            ({{ ucfirst($user->role) }})
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Action --}}
            <div>

                <label
                    for="audit-action"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Action
                </label>

                <input
                    id="audit-action"
                    type="text"
                    wire:model.live.debounce.300ms="action"
                    placeholder="e.g. shipment.status_changed"
                    class="w-full rounded-xl border
                           border-slate-300
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

            </div>


            {{-- Entity Type --}}
            <div>

                <label
                    for="audit-entity-type"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Entity Type
                </label>

                <select
                    id="audit-entity-type"
                    wire:model.live="entityType"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

                    <option value="">
                        All types
                    </option>

                    @foreach ($this->entityTypes as $type)

                        <option value="{{ $type }}">
                            {{ $type }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- From Date --}}
            <div>

                <label
                    for="audit-from-date"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    From Date
                </label>

                <input
                    id="audit-from-date"
                    type="date"
                    wire:model.live="fromDate"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

            </div>


            {{-- To Date --}}
            <div>

                <label
                    for="audit-to-date"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    To Date
                </label>

                <input
                    id="audit-to-date"
                    type="date"
                    wire:model.live="toDate"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

            </div>

        </div>


        {{-- Clear --}}
        @if ($action || $entityType || $fromDate || $toDate || $userId)

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


    {{-- ============================================================ --}}
    {{-- Audit Logs Table (read-only) --}}
    {{-- ============================================================ --}}

    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider
                               text-slate-500"
                    >

                        <th class="px-6 py-4">
                            #
                        </th>

                        <th class="px-6 py-4">
                            Action
                        </th>

                        <th class="px-6 py-4">
                            User
                        </th>

                        <th class="px-6 py-4">
                            Entity
                        </th>

                        <th class="px-6 py-4">
                            Metadata
                        </th>

                        <th class="px-6 py-4">
                            Created At
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->auditLogs as $log)

                        <tr class="transition hover:bg-slate-50">

                            {{-- # --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <span class="text-xs text-slate-400">
                                    #{{ $log->id }}
                                </span>

                            </td>


                            {{-- Action --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @php
                                    $actionClasses = match (true) {
                                        str_contains($log->action, 'delete') => 'bg-red-100 text-red-700',
                                        str_contains($log->action, 'login') => 'bg-slate-100 text-slate-700',
                                        str_contains($log->action, 'status') => 'bg-blue-100 text-blue-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp

                                <span
                                    class="inline-flex rounded-full
                                           px-3 py-1 text-xs
                                           font-semibold {{ $actionClasses }}"
                                >
                                    {{ $log->action }}
                                </span>

                            </td>


                            {{-- User --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if ($log->user)

                                    <div class="font-medium text-slate-900">
                                        {{ $log->user->name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ ucfirst($log->user->role) }}
                                    </div>

                                @else

                                    <span class="text-sm font-medium text-slate-400">
                                        System
                                    </span>

                                @endif

                            </td>


                            {{-- Entity --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if ($log->entity_type)

                                    <div class="text-sm font-medium text-slate-900">
                                        {{ $log->entity_type }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        ID: {{ $log->entity_id ?? '—' }}
                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Metadata --}}
                            <td class="px-6 py-4">

                                @if ($log->metadata)

                                    <details class="max-w-md">

                                        <summary
                                            class="cursor-pointer text-xs
                                                   font-semibold text-blue-600
                                                   hover:text-blue-700"
                                        >
                                            View metadata
                                        </summary>

                                        <pre
                                            class="mt-2 overflow-x-auto
                                                   rounded-lg bg-slate-50 p-3
                                                   text-xs text-slate-600"
                                        >{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                                    </details>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Created At --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <span class="text-sm text-slate-600">
                                    {{ Carbon::parse($log->created_at)->format('M j, Y g:i A') }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="px-6 py-12 text-center">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No audit logs found
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


        {{-- ======================================================== --}}
        {{-- Pagination --}}
        {{-- ======================================================== --}}

        @if ($this->auditLogs->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->auditLogs->links() }}

            </div>

        @endif

    </div>

</div>

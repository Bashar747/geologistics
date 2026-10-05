<?php

use App\Models\SentNotification;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $channel = '';

    public string $status = '';

    public int $perPage = 15;

    // حقول نموذج الإرسال (أدمن/موزّع فقط)
    public int $selectedUserId = 0;

    public string $selectedChannel = 'sms';

    public string $message = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedChannel(): void
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
        $this->channel = '';
        $this->status = '';

        $this->resetPage();
    }

    private function canManage(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'dispatcher'], true);
    }

    public function getNotificationsProperty()
    {
        $query = SentNotification::query()
            ->with('user:id,name,role');

        // نفس قاعدة الـ NotificationController:
        // المستخدم العادي يشوف إشعاراته بس، الأدمن/الموزّع يشوفوا الكل
        if (! $this->canManage()) {
            $query->where('user_id', auth()->id());
        }

        return $query
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where('message', 'like', $search);
            })
            ->when($this->channel, function ($query) {
                $query->where('channel', $this->channel);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function getUsersProperty()
    {
        return User::query()
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();
    }

    public function sendNotification(): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to send notifications.');

            return;
        }

        $validated = $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'selectedChannel' => ['required', 'in:sms,push,email'],
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'selectedUserId.required' => 'Please select a user.',
            'selectedUserId.exists' => 'Please select a valid user.',
            'selectedChannel.in' => 'Please select a valid channel.',
            'message.required' => 'Please enter a message.',
        ]);

        // نفس سلوك الـ NotificationController@store:
        // محاكاة إرسال فوري (بدون خدمة خارجية) — status=sent و sent_at=now
        SentNotification::create([
            'user_id' => $validated['selectedUserId'],
            'channel' => $validated['selectedChannel'],
            'message' => $validated['message'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->reset(['selectedUserId', 'selectedChannel', 'message']);

        $this->selectedChannel = 'sms';

        session()->flash('success', 'Notification sent successfully.');
    }

    public function retryNotification(int $notificationId): void
    {
        if (! $this->canManage()) {
            session()->flash('error', 'You are not authorized to retry notifications.');

            return;
        }

        $notification = SentNotification::findOrFail($notificationId);

        // نفس حارس الـ backend: الإشعارات الفاشلة فقط يلي بينفع إعادة إرسالها
        if ($notification->status !== 'failed') {
            session()->flash('error', 'Only failed notifications can be retried.');

            return;
        }

        // نفس محاكاة إعادة الإرسال في الـ backend
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        session()->flash('success', 'Notification resent successfully.');
    }
};
?>

<div class="space-y-6">

    {{-- ============================================================ --}}
    {{-- Header --}}
    {{-- ============================================================ --}}

    <div>

        <h1 class="text-2xl font-bold text-slate-900">
            Notifications
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))
                All sent notifications across the system.
            @else
                Your sent notifications.
            @endif
        </p>

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
    {{-- Send form (admin/dispatcher only) --}}
    {{-- ============================================================ --}}

    @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

        <div class="rounded-2xl border border-slate-200 bg-white">

            <div class="border-b border-slate-200 px-6 py-5">

                <h2 class="text-lg font-semibold text-slate-900">
                    Send Notification
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Send a notification to a specific user.
                </p>

            </div>

            <div class="p-6">

                <form wire:submit="sendNotification" class="space-y-5">

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        {{-- User --}}
                        <div>

                            <label
                                for="notification-user"
                                class="mb-2 block text-sm font-medium
                                       text-slate-700"
                            >
                                User
                            </label>

                            <select
                                id="notification-user"
                                wire:model="selectedUserId"
                                class="w-full rounded-xl border
                                       border-slate-300 bg-white
                                       px-4 py-3
                                       outline-none transition
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
                            >

                                <option value="0">
                                    Select a user...
                                </option>

                                @foreach ($this->users as $user)

                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                        ({{ ucfirst($user->role) }})
                                    </option>

                                @endforeach

                            </select>

                            @error('selectedUserId')

                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Channel --}}
                        <div>

                            <label
                                for="notification-channel"
                                class="mb-2 block text-sm font-medium
                                       text-slate-700"
                            >
                                Channel
                            </label>

                            <select
                                id="notification-channel"
                                wire:model="selectedChannel"
                                class="w-full rounded-xl border
                                       border-slate-300 bg-white
                                       px-4 py-3
                                       outline-none transition
                                       focus:border-blue-500
                                       focus:ring-4
                                       focus:ring-blue-500/10"
                            >

                                <option value="sms">
                                    SMS
                                </option>

                                <option value="email">
                                    Email
                                </option>

                                <option value="push">
                                    Push
                                </option>

                            </select>

                            @error('selectedChannel')

                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>


                    {{-- Message --}}
                    <div>

                        <label
                            for="notification-message"
                            class="mb-2 block text-sm font-medium
                                   text-slate-700"
                        >
                            Message
                        </label>

                        <textarea
                            id="notification-message"
                            wire:model="message"
                            rows="3"
                            placeholder="Notification message..."
                            class="w-full rounded-xl border
                                   border-slate-300
                                   px-4 py-3
                                   outline-none transition
                                   focus:border-blue-500
                                   focus:ring-4
                                   focus:ring-blue-500/10"
                        ></textarea>

                        @error('message')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    {{-- Submit --}}
                    <div class="flex items-center justify-end">

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center
                                   justify-center rounded-xl
                                   bg-blue-600 px-5 py-3
                                   text-sm font-semibold
                                   text-white transition
                                   hover:bg-blue-700"
                        >
                            Send Notification
                        </button>

                    </div>

                </form>

            </div>

        </div>

    @endif


    {{-- ============================================================ --}}
    {{-- Filters --}}
    {{-- ============================================================ --}}

    <div
        class="rounded-2xl border border-slate-200
               bg-white p-5"
    >

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

            {{-- Search --}}
            <div class="md:col-span-2">

                <label
                    for="notification-search"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Search
                </label>

                <div class="relative">

                    <span
                        class="pointer-events-none absolute
                               left-4 top-1/2 -translate-y-1/2
                               text-slate-400"
                    >
                        🔍
                    </span>

                    <input
                        id="notification-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search message..."
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


            {{-- Channel --}}
            <div>

                <label
                    for="notification-channel-filter"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Channel
                </label>

                <select
                    id="notification-channel-filter"
                    wire:model.live="channel"
                    class="w-full rounded-xl border
                           border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4
                           focus:ring-blue-500/10"
                >

                    <option value="">
                        All channels
                    </option>

                    <option value="sms">
                        SMS
                    </option>

                    <option value="email">
                        Email
                    </option>

                    <option value="push">
                        Push
                    </option>

                </select>

            </div>


            {{-- Status --}}
            <div>

                <label
                    for="notification-status"
                    class="mb-2 block text-sm font-medium
                           text-slate-700"
                >
                    Status
                </label>

                <select
                    id="notification-status"
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

                    <option value="sent">
                        Sent
                    </option>

                    <option value="failed">
                        Failed
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                </select>

            </div>

        </div>


        {{-- Clear --}}
        @if ($search || $channel || $status)

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
    {{-- Notifications Table --}}
    {{-- ============================================================ --}}

    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider
                               text-slate-500"
                    >

                        @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

                            <th class="px-6 py-4">
                                User
                            </th>

                        @endif

                        <th class="px-6 py-4">
                            Channel
                        </th>

                        <th class="px-6 py-4">
                            Message
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                        <th class="px-6 py-4">
                            Sent At
                        </th>

                        @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

                            <th class="px-6 py-4 text-right">
                                Actions
                            </th>

                        @endif

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->notifications as $notification)

                        <tr class="transition hover:bg-slate-50">

                            {{-- User --}}
                            @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

                                <td class="whitespace-nowrap px-6 py-5">

                                    <div class="font-medium text-slate-900">
                                        {{ $notification->user?->name ?? 'Deleted user' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        #{{ $notification->user_id }}
                                    </div>

                                </td>

                            @endif


                            {{-- Channel --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $channelClasses = match ($notification->channel) {
                                        'sms' => 'bg-blue-100 text-blue-700',
                                        'email' => 'bg-violet-100 text-violet-700',
                                        'push' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <span
                                    class="inline-flex rounded-full
                                           px-3 py-1 text-xs
                                           font-semibold {{ $channelClasses }}"
                                >
                                    {{ strtoupper($notification->channel) }}
                                </span>

                            </td>


                            {{-- Message --}}
                            <td class="px-6 py-5">

                                <div class="max-w-md truncate text-sm
                                            text-slate-600"
                                     title="{{ $notification->message }}"
                                >
                                    {{ Str::limit($notification->message, 80) }}
                                </div>

                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $statusClasses = match ($notification->status) {
                                        'sent' => 'bg-emerald-100 text-emerald-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <span
                                    class="inline-flex rounded-full
                                           px-3 py-1 text-xs
                                           font-semibold {{ $statusClasses }}"
                                >
                                    {{ ucfirst($notification->status) }}
                                </span>

                            </td>


                            {{-- Sent At --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($notification->sent_at)

                                    <span class="text-sm text-slate-600">
                                        {{ $notification->sent_at->format('M j, Y g:i A') }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            @if (in_array(auth()->user()->role, ['admin', 'dispatcher'], true))

                                <td class="whitespace-nowrap px-6 py-5 text-right">

                                    @if ($notification->status === 'failed')

                                        <button
                                            type="button"
                                            wire:click="retryNotification({{ $notification->id }})"
                                            wire:loading.attr="disabled"
                                            class="inline-flex rounded-lg
                                                   bg-blue-600 px-3 py-1.5
                                                   text-xs font-semibold text-white
                                                   transition hover:bg-blue-700"
                                        >
                                            Retry
                                        </button>

                                    @else

                                        <span class="text-xs text-slate-400">
                                            —
                                        </span>

                                    @endif

                                </td>

                            @endif

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="{{ in_array(auth()->user()->role, ['admin', 'dispatcher'], true) ? 6 : 4 }}"
                                class="px-6 py-12 text-center"
                            >

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No notifications found
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

        @if ($this->notifications->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->notifications->links() }}

            </div>

        @endif

    </div>

</div>

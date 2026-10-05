
<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $phone = '';

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->name = $this->user->name ?? '';
        $this->email = $this->user->email ?? '';
        $this->phone = $this->user->phone ?? '';
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $this->user->id,
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $this->user->refresh();

        session()->flash('success', 'Profile updated successfully.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:8',
                'same:password_confirmation',
            ],
            'password_confirmation' => ['required', 'string'],
        ]);

        $this->user->update([
            'password' => $this->password,
        ]);

        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        session()->flash(
            'password_success',
            'Password changed successfully.'
        );
    }
};
?>

<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">
            Profile
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Manage your personal account information.
        </p>
    </div>

    {{-- Profile Success --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Account + Personal Information --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Account Overview --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-xl font-bold text-blue-600">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div class="min-w-0">

                    <h2 class="truncate text-lg font-semibold text-slate-900">
                        {{ $user->name }}
                    </h2>

                    <p class="truncate text-sm text-slate-500">
                        {{ $user->email }}
                    </p>

                </div>

            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Account Role
                </p>

                <span class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-medium capitalize text-slate-700">
                    {{ $user->role }}
                </span>

            </div>

        </div>

        {{-- Personal Information --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

            <div class="mb-6">

                <h2 class="text-lg font-semibold text-slate-900">
                    Personal Information
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Update the information associated with your account.
                </p>

            </div>

            <form wire:submit="updateProfile" class="space-y-5">

                {{-- Name --}}
                <div>

                    <label
                        for="name"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Full Name
                    </label>

                    <input
                        id="name"
                        type="text"
                        wire:model="name"
                        autocomplete="name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                    @error('name')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Email --}}
                <div>

                    <label
                        for="email"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Email Address
                    </label>

                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                    @error('email')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Phone --}}
                <div>

                    <label
                        for="phone"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Phone Number
                    </label>

                    <input
                        id="phone"
                        type="text"
                        wire:model="phone"
                        autocomplete="tel"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Save --}}
                <div class="flex justify-end border-t border-slate-100 pt-5">

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >

                        <span wire:loading.remove wire:target="updateProfile">
                            Save Changes
                        </span>

                        <span wire:loading wire:target="updateProfile">
                            Saving...
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

    {{-- Change Password --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-6">

            <h2 class="text-lg font-semibold text-slate-900">
                Change Password
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Update your account password securely.
            </p>

        </div>

        {{-- Password Success --}}
        @if (session('password_success'))
            <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('password_success') }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="space-y-5">

            {{-- Current Password --}}
            <div>

                <label
                    for="current_password"
                    class="mb-1.5 block text-sm font-medium text-slate-700"
                >
                    Current Password
                </label>

                <input
                    id="current_password"
                    type="password"
                    wire:model="current_password"
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >

                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- New Password --}}
                <div>

                    <label
                        for="password"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        New Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                    @error('password')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Confirm Password --}}
                <div>

                    <label
                        for="password_confirmation"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Confirm New Password
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        wire:model="password_confirmation"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

            {{-- Change Password Button --}}
            <div class="flex justify-end border-t border-slate-100 pt-5">

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >

                    <span wire:loading.remove wire:target="updatePassword">
                        Change Password
                    </span>

                    <span wire:loading wire:target="updatePassword">
                        Updating...
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>
```
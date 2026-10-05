<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public int $perPage = 15;

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $userRole = 'customer';

    public string $password = '';
 

    public function mount(): void
{
    abort_unless(
        auth()->user()?->role === 'admin',
        403
    );
}
    public function updatedSearch(): void
    {

        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->role = '';

        $this->resetPage();
    }

    public function createUser(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $this->resetForm();

        $this->showForm = true;
    }

    public function editUser(int $userId): void
    {

    abort_unless(auth()->user()?->role === 'admin', 403);
        $user = User::findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->userRole = $user->role;
        $this->password = '';

        $this->resetValidation();

        $this->showForm = true;
    }
     public function deleteUser(int $userId): void
{


abort_unless(auth()->user()?->role === 'admin', 403);
    $user = User::findOrFail($userId);

    // Prevent deleting the currently logged-in user
    if ($user->id === auth()->id()) {
        session()->flash('error', 'You cannot disable your own account.');
        return;
    }

    $user->delete();

    session()->flash('success', 'User disabled successfully.');
}
    public function cancelForm(): void
    {
        $this->showForm = false;

        $this->resetForm();
    }

    public function saveUser(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email,' . ($this->editingUserId ?? 'NULL'),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                'unique:users,phone,' . ($this->editingUserId ?? 'NULL'),
            ],
            'userRole' => ['required', 'in:customer,driver,dispatcher,admin'],
        ];

        if (!$this->editingUserId) {
            $rules['password'] = ['required', 'string', 'min:8'];
        } elseif ($this->password !== '') {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        }

        $this->validate($rules);

        $user = $this->editingUserId
            ? User::findOrFail($this->editingUserId)
            : new User();

        $user->name = $this->name;
        $user->email = $this->email ?: null;
        $user->phone = $this->phone ?: null;
        $user->role = $this->userRole;

        if ($this->password !== '') {
            $user->password = $this->password;
        }

        $user->save();

        session()->flash(
            'success',
            $this->editingUserId
                ? 'User updated successfully.'
                : 'User created successfully.'
        );

        $this->cancelForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingUserId',
            'name',
            'email',
            'phone',
            'password',
        ]);

        $this->userRole = 'customer';

        $this->resetValidation();
    }

    public function getUsersProperty()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($this->role, function ($query) {
                $query->where('role', $this->role);
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div>

        <h1 class="text-2xl font-bold text-slate-900">
            Users
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            All registered users in the system.
        </p>
        <div class="mt-4">
    <button
        type="button"
        wire:click="createUser"
        class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white
               shadow-sm transition hover:bg-blue-700"
    >
        + Create User
    </button>
</div>

    </div>
   @if ($showForm)

    <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-6">

        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-900">
                {{ $editingUserId ? 'Edit User' : 'Create User' }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $editingUserId
                    ? 'Update the user information below.'
                    : 'Create a new user account.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Name
                </label>

                <input
                    type="text"
                    wire:model="name"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3
                           outline-none focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Email
                </label>

                <input
                    type="email"
                    wire:model="email"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3
                           outline-none focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Phone
                </label>

                <input
                    type="text"
                    wire:model="phone"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3
                           outline-none focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Role
                </label>

                <select
                    wire:model="userRole"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3
                           outline-none focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >
                    <option value="customer">Customer</option>
                    <option value="driver">Driver</option>
                    <option value="dispatcher">Dispatcher</option>
                    <option value="admin">Admin</option>
                </select>

                @error('userRole')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Password
                    @if ($editingUserId)
                        <span class="font-normal text-slate-500">
                            (leave empty to keep current password)
                        </span>
                    @endif
                </label>

                <input
                    type="password"
                    wire:model="password"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3
                           outline-none focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <div class="mt-6 flex items-center gap-3">

            <button
                type="button"
                wire:click="saveUser"
                wire:loading.attr="disabled"
                class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white
                       transition hover:bg-blue-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="saveUser">
                    {{ $editingUserId ? 'Update User' : 'Create User' }}
                </span>

                <span wire:loading wire:target="saveUser">
                    Saving...
                </span>
            </button>

            <button
                type="button"
                wire:click="cancelForm"
                class="rounded-xl border border-slate-300 bg-white px-5 py-3
                       text-sm font-semibold text-slate-700 transition
                       hover:bg-slate-50"
            >
                Cancel
            </button>

        </div>

    </div>

@endif

    {{-- Filters --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Search --}}
            <div class="md:col-span-2">

                <label
                    for="user-search"
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
                        id="user-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, email or phone..."
                        class="w-full rounded-xl border border-slate-300
                               px-4 py-3 pl-11
                               outline-none transition
                               focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                </div>

            </div>


            {{-- Role --}}
            <div>

                <label
                    for="user-role"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Role
                </label>

                <select
                    id="user-role"
                    wire:model.live="role"
                    class="w-full rounded-xl border border-slate-300 bg-white
                           px-4 py-3
                           outline-none transition
                           focus:border-blue-500
                           focus:ring-4 focus:ring-blue-500/10"
                >

                    <option value="">
                        All roles
                    </option>

                    <option value="admin">
                        Admin
                    </option>

                    <option value="dispatcher">
                        Dispatcher
                    </option>

                    <option value="driver">
                        Driver
                    </option>

                    <option value="customer">
                        Customer
                    </option>

                </select>

            </div>

        </div>


        {{-- Clear --}}
        @if ($search || $role)

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


    {{-- Users Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold
                               uppercase tracking-wider text-slate-500"
                    >

                        <th class="px-6 py-4">
                            Name
                        </th>

                        <th class="px-6 py-4">
                            Email
                        </th>

                        <th class="px-6 py-4">
                            Phone
                        </th>

                        <th class="px-6 py-4">
                            Role
                        </th>

                       <th class="px-6 py-4">
    Registered
</th>

<th class="px-6 py-4 text-right">
    Actions
</th>
             
                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->users as $user)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Name --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-full bg-slate-100
                                                text-sm font-bold text-slate-600"
                                    >
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>

                                    <div class="font-semibold text-slate-900">
                                        {{ $user->name }}
                                    </div>

                                </div>

                            </td>

                            <td class="whitespace-nowrap px-6 py-5 text-right">

    <div class="flex justify-end gap-2">

        <button
            type="button"
            wire:click="editUser({{ $user->id }})"
            class="rounded-lg px-3 py-2 text-sm font-semibold
                   text-blue-600 transition hover:bg-blue-50 hover:text-blue-700"
        >
            Edit
        </button>

        <button
            type="button"
            wire:click="deleteUser({{ $user->id }})"
            wire:confirm="Are you sure you want to disable this user?"
            class="rounded-lg px-3 py-2 text-sm font-semibold
                   text-red-600 transition hover:bg-red-50 hover:text-red-700"
        >
            Delete
        </button>

    </div>

</td>
                            {{-- Email --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($user->email)

                                    <span class="text-sm text-slate-600">
                                        {{ $user->email }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Phone --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @if ($user->phone)

                                    <span class="text-sm text-slate-600">
                                        {{ $user->phone }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Role --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                @php
                                    $roleClasses = match ($user->role) {
                                        'admin' => 'bg-violet-100 text-violet-700',
                                        'dispatcher' => 'bg-blue-100 text-blue-700',
                                        'driver' => 'bg-emerald-100 text-emerald-700',
                                        'customer' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <span class="inline-flex rounded-full
                                             px-3 py-1 text-xs
                                             font-semibold {{ $roleClasses }}"
                                >
                                    {{ ucfirst($user->role) }}
                                </span>

                            </td>


                            {{-- Registered --}}
                            <td class="whitespace-nowrap px-6 py-5">

                                <span class="text-sm text-slate-600">
                                    {{ $user->created_at->format('M j, Y') }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="px-6 py-12 text-center">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    No users found
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
        @if ($this->users->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $this->users->links() }}

            </div>

        @endif

    </div>

</div>

<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'customer';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
            'role' => ['required', Rule::in(['customer', 'driver'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?: null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        if ($user->role === 'driver') {
            $user->driverProfile()->create([
                'status' => 'available',
                'joined_at' => now(),
            ]);
        }

        session()->flash('success', 'Account created successfully. You can now sign in.');

        $this->redirect('/login', navigate: true);
    }
};
?>

<div class="min-h-screen bg-slate-950 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-lg">

        {{-- Brand --}}
        <div class="text-center mb-8">

            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/30 mb-5">
                <svg
                    class="w-8 h-8 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M12 21s8-4.5 8-10a8 8 0 10-16 0c0 5.5 8 10 8 10z"
                    />
                    <circle
                        cx="12"
                        cy="11"
                        r="2.5"
                        stroke-width="1.8"
                    />
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-white">
                GeoLogistics
            </h1>

            <p class="mt-2 text-sm text-slate-400">
                Create your account
            </p>

        </div>


        {{-- Register Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <div class="mb-7">

                <h2 class="text-2xl font-bold text-slate-900">
                    Create account
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Enter your information to get started.
                </p>

            </div>


            <form wire:submit="register" class="space-y-5">

                {{-- Name --}}
                <div>

                    <label
                        for="name"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Full name
                    </label>

                    <input
                        id="name"
                        type="text"
                        wire:model="name"
                        autocomplete="name"
                        placeholder="Your full name"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300
                               text-slate-900 placeholder-slate-400
                               outline-none transition
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


                {{-- Phone --}}
                <div>

                    <label
                        for="phone"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Phone number
                    </label>

                    <input
                        id="phone"
                        type="text"
                        wire:model="phone"
                        autocomplete="tel"
                        placeholder="+1 555 123 4567"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300
                               text-slate-900 placeholder-slate-400
                               outline-none transition
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('phone')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


                {{-- Email --}}
                <div>

                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Email
                        <span class="text-slate-400">(optional)</span>
                    </label>

                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300
                               text-slate-900 placeholder-slate-400
                               outline-none transition
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


                {{-- Role --}}
                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Account type
                    </label>

                    <div class="grid grid-cols-2 gap-3">

                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                wire:model="role"
                                value="customer"
                                class="sr-only peer"
                            >

                            <div class="rounded-xl border border-slate-300 p-4
                                        peer-checked:border-blue-500
                                        peer-checked:bg-blue-50
                                        transition">

                                <div class="font-semibold text-slate-900">
                                    Customer
                                </div>

                                <div class="text-xs text-slate-500 mt-1">
                                    Send and track shipments
                                </div>

                            </div>
                        </label>


                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                wire:model="role"
                                value="driver"
                                class="sr-only peer"
                            >

                            <div class="rounded-xl border border-slate-300 p-4
                                        peer-checked:border-blue-500
                                        peer-checked:bg-blue-50
                                        transition">

                                <div class="font-semibold text-slate-900">
                                    Driver
                                </div>

                                <div class="text-xs text-slate-500 mt-1">
                                    Manage assigned deliveries
                                </div>

                            </div>
                        </label>

                    </div>

                    @error('role')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


                {{-- Password --}}
                <div>

                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="new-password"
                        placeholder="Minimum 8 characters"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300
                               text-slate-900 placeholder-slate-400
                               outline-none transition
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


                {{-- Confirm Password --}}
                <div>

                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Confirm password
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        wire:model="password_confirmation"
                        autocomplete="new-password"
                        placeholder="Repeat your password"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300
                               text-slate-900 placeholder-slate-400
                               outline-none transition
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>


                {{-- Submit --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2
                           px-4 py-3 rounded-xl
                           bg-blue-600 text-white font-semibold
                           shadow-lg shadow-blue-600/20
                           hover:bg-blue-700
                           focus:outline-none focus:ring-4 focus:ring-blue-500/20
                           disabled:opacity-60 disabled:cursor-not-allowed
                           transition"
                >

                    <span wire:loading.remove wire:target="register">
                        Create account
                    </span>

                    <span
                        wire:loading
                        wire:target="register"
                    >
                        Creating account...
                    </span>

                </button>

            </form>


            {{-- Login --}}
            <div class="mt-7 pt-6 border-t border-slate-100 text-center">

                <span class="text-sm text-slate-500">
                    Already have an account?
                </span>

                <a
                    href="/login"
                    wire:navigate
                    class="text-sm font-semibold text-blue-600 hover:text-blue-700 ml-1"
                >
                    Sign in
                </a>

            </div>

        </div>

    </div>

</div>
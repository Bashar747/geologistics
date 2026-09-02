<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public string $phone = '';

    public string $password = '';

    public function login(): void
    {
        $this->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $this->phone)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('phone', 'The phone number or password is incorrect.');

            return;
        }

        Auth::login($user);

        request()->session()->regenerate();

        $this->redirect('/dashboard', navigate: true);
    }
};
?>

<div class="min-h-screen bg-slate-950 flex items-center justify-center px-4 py-12">

    <div class="w-full max-w-md">

        {{-- Logo / Brand --}}
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

            <h1 class="text-3xl font-bold text-white tracking-tight">
                GeoLogistics
            </h1>

            <p class="mt-2 text-sm text-slate-400">
                Logistics management system
            </p>

        </div>


        {{-- Login Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <div class="mb-7">

                <h2 class="text-2xl font-bold text-slate-900">
                    Welcome back
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Sign in to continue to your dashboard.
                </p>

            </div>


            <form wire:submit="login" class="space-y-5">

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
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
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
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300
                               text-slate-900 placeholder-slate-400
                               outline-none transition
                               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Login Button --}}
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

                    <span wire:loading.remove wire:target="login">
                        Sign in
                    </span>

                    <span
                        wire:loading
                        wire:target="login"
                        class="flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>

                        Signing in...
                    </span>

                </button>

            </form>


            {{-- Footer --}}
            <div class="mt-8 pt-6 border-t border-slate-100 text-center">

                <p class="text-xs text-slate-400">
                    GeoLogistics Management System
                </p>

            </div>

        </div>

    </div>

</div>
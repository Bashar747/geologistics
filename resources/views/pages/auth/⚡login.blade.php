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

<div class="relative min-h-screen bg-[#05070d] text-white flex items-center justify-center px-4 py-12 overflow-hidden">

    {{-- Background --}}
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-0"
    >
        <div
            class="absolute left-1/2 top-[-260px] h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-blue-600/10 blur-[150px]"
        ></div>

        <div
            class="absolute right-[-200px] bottom-[-200px] h-[500px] w-[500px] rounded-full bg-violet-600/8 blur-[150px]"
        ></div>

        <div
            class="absolute inset-0 opacity-[0.018]"
            style="
                background-image:
                    linear-gradient(rgba(255,255,255,.7) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.7) 1px, transparent 1px);
                background-size: 56px 56px;
            "
        ></div>
    </div>


    <div class="relative z-10 w-full max-w-md">

        {{-- Brand --}}
        <div class="text-center mb-8">

            <a
                href="/"
                class="inline-flex items-center gap-3 group"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-2xl
                           bg-blue-600 shadow-lg shadow-blue-600/30
                           transition group-hover:bg-blue-500"
                >
                    <svg
                        class="w-6 h-6 text-white"
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

                <span class="text-xl font-bold tracking-tight">
                    GeoLogistics
                </span>
            </a>

            <p class="mt-3 text-sm text-slate-400">
                Logistics management system
            </p>

        </div>


        {{-- Login Card --}}
        <div
            class="rounded-2xl border border-white/10
                   bg-[#0b1020]/95 backdrop-blur-xl
                   shadow-2xl shadow-black/40 p-8"
        >

            {{-- Header --}}
            <div class="mb-7">

                <div
                    class="inline-flex items-center px-3 py-1 rounded-full
                           bg-blue-500/10 border border-blue-500/20
                           text-xs font-medium text-blue-400 mb-4"
                >
                    Secure access
                </div>

                <h1 class="text-2xl font-bold text-white tracking-tight">
                    Welcome back
                </h1>

                <p class="mt-2 text-sm text-slate-400">
                    Sign in to continue to your dashboard.
                </p>

            </div>


            {{-- Form --}}
            <form wire:submit="login" class="space-y-5">

                {{-- Phone --}}
                <div>

                    <label
                        for="phone"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Phone number
                    </label>

                    <input
                        id="phone"
                        type="text"
                        wire:model="phone"
                        autocomplete="tel"
                        placeholder="+1 555 123 4567"
                        class="w-full px-4 py-3 rounded-xl
                               border border-white/10
                               bg-white/[0.04]
                               text-white placeholder-slate-500
                               outline-none transition
                               focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('phone')
                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Password --}}
                <div>

                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        class="w-full px-4 py-3 rounded-xl
                               border border-white/10
                               bg-white/[0.04]
                               text-white placeholder-slate-500
                               outline-none transition
                               focus:border-blue-500
                               focus:ring-4 focus:ring-blue-500/10"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-400">
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
                           hover:bg-blue-500
                           focus:outline-none focus:ring-4
                           focus:ring-blue-500/20
                           disabled:opacity-60
                           disabled:cursor-not-allowed
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


            {{-- Create Account --}}
            <div class="relative my-7">

                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/10"></div>
                </div>

                <div class="relative flex justify-center">
                    <span class="px-3 bg-[#0b1020] text-xs text-slate-500">
                        New to GeoLogistics?
                    </span>
                </div>

            </div>


            <a
                href="/register"
                class="w-full flex items-center justify-center
                       px-4 py-3 rounded-xl
                       border border-white/10
                       bg-white/[0.03]
                       text-white font-semibold
                       hover:bg-white/[0.07]
                       hover:border-white/20
                       transition"
            >
                Create an account
            </a>


            {{-- Footer --}}
            <div class="mt-7 text-center">

                <p class="text-xs text-slate-500">
                    GeoLogistics Management System
                </p>

            </div>

        </div>


        {{-- Back to home --}}
        <div class="mt-6 text-center">

            <a
                href="/"
                class="text-sm text-slate-500 hover:text-slate-300 transition"
            >
                ← Back to homepage
            </a>

        </div>

    </div>

</div>
@extends('layouts.guest')

@section('title', 'Login - ' . config('app.name'))

@section('content')
<div class="w-full max-w-md animate-slide-up">
    <!-- Brand -->
    <div class="flex flex-col items-center justify-center gap-3 mb-8">
        <x-logo variant="logo-text" size="lg" />
    </div>

    <!-- Card -->
    <x-card variant="bordered" padding="lg">
        <header class="text-center mb-6">
            <h2 class="text-2xl font-semibold mb-1" style="color: var(--text-primary);">Officer Sign In</h2>
            <p class="text-xs sm:text-sm" style="color: var(--text-secondary);">Sign in to access your workspace</p>
        </header>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">
                {{ session('status') }}
            </x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <x-input
                type="email"
                name="email"
                label="Email address"
                placeholder="Enter your email"
                required
                autocomplete="email"
                autofocus
                :error="$errors->first('email')"
                :value="old('email')"
            />

            <x-input
                type="password"
                name="password"
                label="Password"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
                :error="$errors->first('password')"
                showPasswordToggle

            />

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        checked
                        class="w-4 h-4 rounded" style="border-color: var(--border-primary); accent-color: var(--brand);"
                    >
                    <span class="text-xs" style="color: var(--text-secondary);">Remember me</span>
                </label>

                <x-link href="{{ route('password.request') }}" variant="default" size="sm">
                    Forgot password?
                </x-link>
            </div>

            <x-button type="submit" variant="primary" size="lg" fullWidth class="mt-2">
                Sign in to Copilot
            </x-button>
        </form>

        <!-- Evaluation Quick Credentials -->
        {{-- <div class="mt-6 p-3 rounded-2xl bg-gray-50 dark:bg-[#141414] border border-gray-200/80 dark:border-gray-800 text-[11px] text-gray-500 dark:text-gray-400">
            <div class="font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center justify-between">
                <span>Demo Evaluation Accounts:</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono">Ready</span>
            </div>
            <div class="flex items-center justify-between py-0.5">
                <span>Officer: <code class="font-mono text-blue-600 dark:text-blue-400"></code></span>
                <span class="text-gray-400">pwd: <code class="font-mono"></code></span>
            </div>
            <div class="flex items-center justify-between py-0.5">
                <span>Admin: <code class="font-mono text-blue-600 dark:text-blue-400"></code></span>
                <span class="text-gray-400">pwd: <code class="font-mono"></code></span>
            </div>
        </div> --}}
    </x-card>

    <p class="text-center text-xs mt-6" style="color: var(--text-tertiary);">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Domain D4 & Twist T3 Compliant.
    </p>
</div>
@endsection

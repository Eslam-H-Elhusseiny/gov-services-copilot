@extends('layouts.guest')

@section('title', 'Forgot Password - ' . config('app.name'))

@section('content')
<div class="w-full max-w-md animate-slide-up">
    <!-- Brand -->
    <div class="flex flex-col items-center justify-center gap-3 mb-8">
        <x-logo variant="logo-text" size="lg" />
    </div>

    <!-- Card -->
    <x-card variant="bordered" padding="lg">
        <header class="text-center mb-6">
            <h2 class="text-2xl font-semibold mb-1" style="color: var(--text-primary);">Reset Password</h2>
            <p class="text-xs sm:text-sm" style="color: var(--text-secondary);">Enter your registered official email address to receive password reset instructions.</p>
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

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <x-input
                type="email"
                name="email"
                label="Official Email address"
                placeholder="officer@copilot.gov"
                required
                autocomplete="email"
                autofocus
                :error="$errors->first('email')"
                :value="old('email')"
            />

            <x-button type="submit" variant="primary" size="lg" fullWidth class="mt-2">
                Send Reset Link
            </x-button>
        </form>

        <div class="mt-6 text-center">
            <x-link href="{{ route('login') }}" variant="muted" size="sm">
                ← Return to Sign In
            </x-link>
        </div>
    </x-card>

    <p class="text-center text-xs mt-6" style="color: var(--text-tertiary);">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </p>
</div>
@endsection

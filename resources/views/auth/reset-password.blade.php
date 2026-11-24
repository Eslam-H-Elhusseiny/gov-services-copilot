@extends('layouts.guest')

@section('title', 'Set New Password - ' . config('app.name'))

@section('content')
<div class="w-full max-w-md animate-slide-up">
    <!-- Brand -->
    <div class="flex flex-col items-center justify-center gap-3 mb-8">
        <x-logo variant="logo-text" size="lg" />
    </div>

    <!-- Card -->
    <x-card variant="bordered" padding="lg">
        <header class="text-center mb-6">
            <h2 class="text-2xl font-semibold mb-1" style="color: var(--text-primary);">Set New Password</h2>
            <p class="text-xs sm:text-sm" style="color: var(--text-secondary);">Choose a secure password for your government copilot account.</p>
        </header>

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-input
                type="email"
                name="email"
                label="Official Email address"
                placeholder="officer@copilot.gov"
                required
                autocomplete="email"
                :error="$errors->first('email')"
                :value="$request->input('email')"
            />

            <x-input
                type="password"
                name="password"
                label="New password"
                placeholder="Enter new password"
                required
                autocomplete="new-password"
                :error="$errors->first('password')"
                showPasswordToggle
            />

            <x-input
                type="password"
                name="password_confirmation"
                label="Confirm new password"
                placeholder="Confirm new password"
                required
                autocomplete="new-password"
                :error="$errors->first('password_confirmation')"
                showPasswordToggle
            />

            <x-button type="submit" variant="primary" size="lg" fullWidth class="mt-2">
                Update Password & Sign In
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

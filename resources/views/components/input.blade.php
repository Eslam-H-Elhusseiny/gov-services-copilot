@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'hint' => null,
    'id' => null,
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'autocomplete' => null,
    'autofocus' => false,
    'showPasswordToggle' => false,
])

@php
$id = $id ?? $name ?? 'input-' . uniqid();
$type = $showPasswordToggle ? 'password' : $type;
@endphp

<div class="w-full space-y-1.5" {{ $showPasswordToggle ? 'data-password-toggle' : '' }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 ml-0.5">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl">
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($readonly) readonly @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($autofocus) autofocus @endif
            {{ $attributes->merge(['class' => 'w-full rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-[#1a1b1c] text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 border border-gray-200 dark:border-gray-700/80 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-400/20 transition-all duration-150 disabled:bg-gray-50 dark:disabled:bg-gray-800/40 disabled:opacity-60 disabled:cursor-not-allowed ' . ($showPasswordToggle ? 'pr-11 ' : '') . ($error ? 'border-rose-400 dark:border-rose-500 focus:border-rose-500 focus:ring-rose-500/20' : '')]) }}
        />

        @if ($showPasswordToggle)
            <button
                type="button"
                data-password-toggle-button
                aria-label="Toggle password visibility"
                tabindex="-1"
                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" data-password-toggle-visible>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                </svg>
                <svg class="w-4 h-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" data-password-toggle-hidden>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 12 19.5c4.638 0 8.573-3.007 9.963-7.178.07-.207.07-.431 0-.639A10.479 10.479 0 0 0 12 4.5c-4.638 0-8.573 3.007-9.963-7.178-.07.207-.07.431 0-.639z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                </svg>
            </button>
        @endif
    </div>

    @if ($error)
        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $hint }}</p>
    @endif
</div>

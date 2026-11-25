@props([
    'variant' => 'primary', // primary, secondary, outline, ghost, danger, success
    'size' => 'md', // sm, md, lg, xl
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
    'fullWidth' => false,
    'rounded' => 'xl', // md, lg, xl, full
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none disabled:active:scale-100 select-none';

$roundedClasses = [
    'md' => 'rounded-md',
    'lg' => 'rounded-lg',
    'xl' => 'rounded-xl',
    'full' => 'rounded-full',
][$rounded] ?? 'rounded-xl';

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs gap-1.5',
    'md' => 'px-4 py-2 text-sm gap-2 shadow-xs',
    'lg' => 'px-5 py-2.5 text-sm gap-2.5 shadow-sm',
    'xl' => 'px-6 py-3 text-base gap-3 shadow-md',
][$size] ?? 'px-4 py-2 text-sm gap-2';

$variants = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-blue-500/20 focus-visible:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600 dark:active:bg-blue-700',
    'secondary' => 'bg-gray-100 dark:bg-gray-800/90 hover:bg-gray-200 dark:hover:bg-gray-700/80 text-gray-800 dark:text-gray-100 border border-gray-200/80 dark:border-gray-700/60 focus-visible:ring-gray-400',
    'outline' => 'bg-transparent border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-200 focus-visible:ring-gray-400',
    'ghost' => 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white focus-visible:ring-gray-400 shadow-none',
    'danger' => 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-rose-500/20 focus-visible:ring-rose-500 dark:bg-rose-500 dark:hover:bg-rose-600',
    'success' => 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-emerald-500/20 focus-visible:ring-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-600',
][$variant] ?? $variants['primary'];

$widthClass = $fullWidth ? 'w-full' : '';
@endphp

<button
    type="{{ $type }}"
    {{ $disabled || $loading ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => "{$baseClasses} {$roundedClasses} {$sizes} {$variants} {$widthClass}"]) }}
>
    @if ($loading)
        <svg class="animate-spin -ml-0.5 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if (is_string($loading))
            <span>{{ $loading }}</span>
        @else
            <span>{{ $slot }}</span>
        @endif
    @else
        {{ $slot }}
    @endif
</button>

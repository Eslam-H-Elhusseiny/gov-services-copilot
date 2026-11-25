@props([
    'variant' => 'neutral', // neutral, success, warning, danger, info, purple
    'size' => 'md', // sm, md, lg
    'dot' => false,
])

@php
$variants = [
    'neutral' => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700',
    'success' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
    'warning' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
    'danger' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60',
    'info' => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60',
    'purple' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/60',
];

$dotColors = [
    'neutral' => 'bg-gray-400 dark:bg-gray-500',
    'success' => 'bg-emerald-500',
    'warning' => 'bg-amber-500',
    'danger' => 'bg-rose-500',
    'info' => 'bg-blue-500',
    'purple' => 'bg-purple-500',
];

$sizes = [
    'sm' => 'text-[11px] px-2 py-0.5 gap-1 font-medium',
    'md' => 'text-xs px-2.5 py-1 gap-1.5 font-medium',
    'lg' => 'text-sm px-3 py-1.5 gap-2 font-medium',
];

$variantClass = $variants[$variant] ?? $variants['neutral'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$dotColor = $dotColors[$variant] ?? $dotColors['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border tracking-wide transition-colors {$variantClass} {$sizeClass}"]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} animate-pulse"></span>
    @endif
    {{ $slot }}
</span>

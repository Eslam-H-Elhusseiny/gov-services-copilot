@props([
    'variant' => 'default', // default, bordered, elevated, subtle, glass
    'padding' => 'md', // none, sm, md, lg, xl
    'rounded' => '2xl', // md, lg, xl, 2xl, 3xl
    'hoverable' => false,
])

@php
$variants = [
    'default' => 'bg-white dark:bg-[#1e1f20] border border-gray-200/80 dark:border-gray-800/80 shadow-xs',
    'bordered' => 'bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800',
    'elevated' => 'bg-white dark:bg-[#1e1f20] border border-gray-200/60 dark:border-gray-800/60 shadow-lg shadow-gray-200/30 dark:shadow-black/40',
    'subtle' => 'bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800/40',
    'glass' => 'bg-white/80 dark:bg-[#1e1f20]/80 backdrop-blur-md border border-gray-200/50 dark:border-gray-700/40 shadow-sm',
];

$paddings = [
    'none' => '',
    'sm' => 'p-4',
    'md' => 'p-6',
    'lg' => 'p-8',
    'xl' => 'p-10',
];

$roundedClasses = [
    'md' => 'rounded-md',
    'lg' => 'rounded-lg',
    'xl' => 'rounded-xl',
    '2xl' => 'rounded-2xl',
    '3xl' => 'rounded-3xl',
];

$variantClass = $variants[$variant] ?? $variants['default'];
$paddingClass = $paddings[$padding] ?? $paddings['md'];
$roundedClass = $roundedClasses[$rounded] ?? 'rounded-2xl';
$hoverClass = $hoverable ? 'transition-all duration-200 hover:border-gray-300 dark:hover:border-gray-700 hover:shadow-md cursor-pointer' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$roundedClass} {$variantClass} {$paddingClass} {$hoverClass}"]) }}>
    {{ $slot }}
</div>

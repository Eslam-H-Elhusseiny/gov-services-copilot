@props([
    'href' => '#',
    'variant' => 'default',
    'size' => 'md',
    'external' => false,
])

<?php
$variants = [
    'default' => 'style="color: var(--brand);" onmouseover="this.style.color=\'var(--brand-hover)\'" onmouseout="this.style.color=\'var(--brand)\'"',
    'muted' => 'style="color: var(--text-secondary);" onmouseover="this.style.color=\'var(--text-primary)\'" onmouseout="this.style.color=\'var(--text-secondary)\'"',
    'danger' => 'style="color: var(--error);" onmouseover="this.style.color=\'color-mix(in srgb, var(--error) 80%, transparent)\'" onmouseout="this.style.color=\'var(--error)\'"',
    'success' => 'style="color: var(--success);" onmouseover="this.style.color=\'color-mix(in srgb, var(--success) 80%, transparent)\'" onmouseout="this.style.color=\'var(--success)\'"',
];

$sizes = [
    'sm' => 'text-xs',
    'md' => 'text-sm',
    'lg' => 'text-base',
];

$variantStyle = $variants[$variant] ?? $variants['default'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$externalAttrs = $external ? 'target="_blank" rel="noopener noreferrer"' : '';
?>

<a
    href="{{ $href }}"
    class="inline-flex items-center gap-1.5 font-medium underline-offset-4 hover:underline transition-colors duration-150 {{ $sizeClass }} {{ $attributes->class ?? '' }}"
    {{ $variantStyle }}
    {{ $externalAttrs }}
    {{ $attributes->except('class') }}
>
    {{ $slot }}
</a>
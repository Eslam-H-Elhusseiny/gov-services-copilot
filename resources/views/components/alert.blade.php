@props([
    'type' => 'info',
    'dismissible' => false,
    'title' => null,
])

<?php
$types = [
    'info' => [
        'bg' => 'style="background-color: color-mix(in srgb, var(--brand) 15%, transparent);"',
        'border' => 'style="border-color: color-mix(in srgb, var(--brand) 20%, transparent);"',
        'text' => 'style="color: var(--brand);"',
        'icon' => 'info',
    ],
    'success' => [
        'bg' => 'style="background-color: color-mix(in srgb, var(--success) 15%, transparent);"',
        'border' => 'style="border-color: color-mix(in srgb, var(--success) 20%, transparent);"',
        'text' => 'style="color: var(--success);"',
        'icon' => 'check',
    ],
    'warning' => [
        'bg' => 'style="background-color: color-mix(in srgb, var(--warning) 15%, transparent);"',
        'border' => 'style="border-color: color-mix(in srgb, var(--warning) 20%, transparent);"',
        'text' => 'style="color: var(--warning);"',
        'icon' => 'exclamation',
    ],
    'error' => [
        'bg' => 'style="background-color: color-mix(in srgb, var(--error) 15%, transparent);"',
        'border' => 'style="border-color: color-mix(in srgb, var(--error) 20%, transparent);"',
        'text' => 'style="color: var(--error);"',
        'icon' => 'x-circle',
    ],
];

$config = $types[$type] ?? $types['info'];
?>

<div class="rounded-lg border p-3 mb-4 {{ $attributes->class ?? '' }}" role="alert" {{ $config['bg'] }} {{ $config['border'] }} {{ $config['text'] }} {{ $attributes->except('class') }}>
    <div class="flex gap-3 justify-center items-center">
        <div class="flex-shrink-0 mt-0.5">
            @if ($config['icon'] === 'info')
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            @elseif ($config['icon'] === 'check')
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000.svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            @elseif ($config['icon'] === 'exclamation')
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000.svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            @elseif ($config['icon'] === 'x-circle')
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000.svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            @if ($title)
                <h4 class="text-sm font-medium">{{ $title }}</h4>
            @endif
            <div class="text-sm {{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        </div>
        @if ($dismissible)
            <button type="button" class="flex-shrink-0 p-1 rounded transition-colors" aria-label="Dismiss" data-dismiss-alert style="color: inherit;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000.svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dismiss-alert]').forEach(button => {
        button.addEventListener('click', () => {
            const alert = button.closest('[role="alert"]');
            if (alert) {
                alert.style.transition = 'opacity 150ms ease, transform 150ms ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-4px)';
                setTimeout(() => alert.remove(), 150);
            }
        });
    });
});
</script>
@endpush

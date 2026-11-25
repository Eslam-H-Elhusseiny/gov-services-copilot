@props([
    'size' => 'md', // sm, md, lg, xl
    'variant' => 'logo-text', // icon, logo, logo-text
    'class' => '',
])

@php
$sizeClasses = [
    'sm' => 'w-7 h-7 text-xs',
    'md' => 'w-9 h-9 text-sm',
    'lg' => 'w-11 h-11 text-base',
    'xl' => 'w-14 h-14 text-xl',
][$size] ?? 'w-9 h-9 text-sm';

$iconSizes = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
    'xl' => 'w-7 h-7',
][$size] ?? 'w-5 h-5';
@endphp

@if ($variant === 'icon' || $variant === 'logo')
    <div class="relative flex items-center justify-center {{ $sizeClasses }} rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 text-white shadow-xs {{ $class }}">
        <svg class="{{ $iconSizes }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
    </div>
@elseif ($variant === 'logo-text')
    <div class="flex items-center gap-3 {{ $class }}">
        <div class="flex items-center justify-center {{ $sizeClasses }} rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 text-white shadow-sm flex-shrink-0">
            <svg class="{{ $iconSizes }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
        </div>
        <div class="flex flex-col text-left">
            <span class="text-sm font-semibold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                Gov Services
                <span class="inline-flex items-center px-1.5 py-0.2 text-[10px] font-semibold uppercase tracking-wider rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/40">Copilot</span>
            </span>
            <span class="text-[11px] text-gray-500 dark:text-gray-400 tracking-normal font-normal">Agentic Citizen Support</span>
        </div>
    </div>
@endif

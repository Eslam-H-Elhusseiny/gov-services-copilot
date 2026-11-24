@props([
    'name',
    'title' => null,
    'maxWidth' => '2xl', // sm, md, lg, xl, 2xl
])

@php
$maxWidthClasses = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')).live }"
    x-show="show"
    x-cloak
    @keydown.escape.window="show = false"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
>
    <!-- Backdrop Blur -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
        class="fixed inset-0 bg-black/60 dark:bg-black/75 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- Modal Card -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidthClasses }} bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 rounded-3xl shadow-2xl p-6 sm:p-7 z-10 overflow-hidden"
    >
        @if ($title)
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800/80 mb-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 tracking-tight">{{ $title }}</h3>
                <button
                    type="button"
                    @click="show = false"
                    class="p-1.5 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{ $slot }}
    </div>
</div>

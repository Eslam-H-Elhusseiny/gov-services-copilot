<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'dark' || (!theme && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @livewireStyles
    @stack('head')
</head>
<body class="h-full overflow-hidden antialiased font-sans select-none" style="background-color: var(--bg-primary); color: var(--text-primary);">
    <!-- Toast Notification Host -->
    <div
        x-data="{
            toasts: [],
            add(toast) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, ...toast });
                setTimeout(() => this.remove(id), 4000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @toast.window="add($event.detail)"
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none max-w-sm w-full"
    >
        <template x-for="t in toasts" :key="t.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="pointer-events-auto p-4 rounded-2xl border shadow-xl flex items-start gap-3 backdrop-blur-md"
                :class="{
                    'bg-white/95 dark:bg-[#1e1f20]/95 border-emerald-500/30 text-emerald-800 dark:text-emerald-200': t.type === 'success',
                    'bg-white/95 dark:bg-[#1e1f20]/95 border-rose-500/30 text-rose-800 dark:text-rose-200': t.type === 'error' || t.type === 'danger',
                    'bg-white/95 dark:bg-[#1e1f20]/95 border-amber-500/30 text-amber-800 dark:text-amber-200': t.type === 'warning',
                    'bg-white/95 dark:bg-[#1e1f20]/95 border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200': !t.type || t.type === 'neutral' || t.type === 'info'
                }"
            >
                <div class="flex-shrink-0 mt-0.5">
                    <template x-if="t.type === 'success'">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'error' || t.type === 'danger'">
                        <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'warning'">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    <template x-if="!t.type || t.type === 'neutral' || t.type === 'info'">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </template>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium leading-relaxed" x-text="t.message || t.title"></p>
                </div>
                <button
                    type="button"
                    @click="remove(t.id)"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Main Viewport Canvas -->
    <div class="h-screen w-screen overflow-hidden flex flex-col">
        @yield('content')
    </div>

    @livewireScripts
    <script>
        // Global theme toggle support
        function toggleAppTheme() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';
            html.classList.toggle('dark');
            localStorage.setItem('theme', newTheme);
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: newTheme } }));
        }
    </script>
    @stack('scripts')
</body>
</html>
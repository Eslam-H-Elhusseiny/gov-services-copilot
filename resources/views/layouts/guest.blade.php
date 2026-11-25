<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css'])

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

    @stack('head')
</head>
<body class="h-full antialiased font-sans" style="background-color: var(--bg-primary); color: var(--text-primary);">
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
        @yield('content')
    </div>

    <!-- Theme Toggle Floating Pill -->
    <button
        type="button"
        data-theme-toggle
        class="fixed bottom-5 right-5 p-2.5 rounded-2xl border shadow-lg z-50 transition-colors backdrop-blur-md"
        style="background-color: var(--surface); border-color: var(--border-primary); color: var(--text-secondary);"
        aria-label="Toggle theme"
    >
        <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" data-theme-icon-sun>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        <svg class="w-5 h-5 flex-shrink-0 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" data-theme-icon-moon>
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
    </button>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleButtons = document.querySelectorAll('[data-theme-toggle]');

            function updateThemeIcons(theme) {
                document.querySelectorAll('[data-theme-icon-sun]').forEach(el => {
                    el.classList.toggle('hidden', theme === 'dark');
                });
                document.querySelectorAll('[data-theme-icon-moon]').forEach(el => {
                    el.classList.toggle('hidden', theme === 'light');
                });
            }

            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            updateThemeIcons(currentTheme);

            themeToggleButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const html = document.documentElement;
                    const isDark = html.classList.contains('dark');
                    const newTheme = isDark ? 'light' : 'dark';

                    html.classList.toggle('dark');
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcons(newTheme);
                });
            });

            // Password toggle
            document.querySelectorAll('[data-password-toggle-button]').forEach(button => {
                button.addEventListener('click', () => {
                    const container = button.closest('[data-password-toggle]') || button.closest('.relative');
                    if (!container) return;

                    const input = container.querySelector('input[type="password"], input[type="text"]');
                    const visibleIcon = container.querySelector('[data-password-toggle-visible]');
                    const hiddenIcon = container.querySelector('[data-password-toggle-hidden]');

                    if (!input || !visibleIcon || !hiddenIcon) return;

                    if (input.type === 'password') {
                        input.type = 'text';
                        visibleIcon.classList.add('hidden');
                        hiddenIcon.classList.remove('hidden');
                    } else {
                        input.type = 'password';
                        visibleIcon.classList.remove('hidden');
                        hiddenIcon.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>
</html>
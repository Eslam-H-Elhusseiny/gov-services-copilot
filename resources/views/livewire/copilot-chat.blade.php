<div
    class="h-full w-full flex overflow-hidden relative"
    x-data="{
        sidebarOpen: window.innerWidth >= 1024,
        isMobile: window.innerWidth < 1024,
        init() {
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 1024;
                if (!this.isMobile && !this.sidebarOpen) {
                    this.sidebarOpen = true;
                }
            });
        }
    }"
>
    <!-- Mobile Backdrop Blur Overlay -->
    <div
        x-show="sidebarOpen && isMobile"
        x-cloak
        @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-out duration-250"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden"
    ></div>

    <!-- Collapsible Sidebar (Drawer on mobile, column on desktop) -->
    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] flex flex-col border-r transition-transform duration-300 ease-in-out lg:static lg:z-30 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:w-0 lg:overflow-hidden lg:border-none'"
        style="background-color: var(--bg-secondary); border-color: var(--border-primary);"
    >
        <div class="flex flex-col h-full w-72 max-w-[85vw]">
            <!-- Brand & Sidebar Toggle -->
            <div class="p-4 flex items-center justify-between border-b" style="border-color: var(--border-primary);">
                <div class="flex items-center gap-2.5">
                    <!-- Authoritative Shield Emblem -->
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 text-white flex items-center justify-center shadow-sm flex-shrink-0">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-semibold tracking-tight leading-none text-gray-900 dark:text-gray-100">Gov Services</h1>
                        <span class="text-[11px] font-medium text-blue-600 dark:text-blue-400">Copilot RAG</span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    title="Close sidebar"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if (! Auth::user()->isAdmin())
            <!-- New Consultation Action Button -->
            <div class="p-3">
                <button
                    type="button"
                    wire:click="newCase"
                    @click="if (isMobile) sidebarOpen = false"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl bg-white dark:bg-[#131314] hover:bg-gray-50 dark:hover:bg-[#282a2c] text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-800 shadow-xs hover:shadow transition-all group"
                >
                    <span class="flex items-center gap-2.5 text-xs font-semibold">
                        <span class="w-5 h-5 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm font-bold leading-none">+</span>
                        New Consultation
                    </span>

                </button>
            </div>
            @endif

            <!-- Navigation Sections -->
            <div class="px-3 py-1 space-y-1">
                @if (! Auth::user()->isAdmin())
                <button
                    type="button"
                    wire:click="$set('activeView', 'consultation')"
                    @click="if (isMobile) sidebarOpen = false"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors"
                    :class="'{{ $activeView }}' === 'consultation' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60'"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                        Consultations
                    </span>
                    <span class="text-[11px] font-mono text-gray-400">{{ count($this->cases) }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('activeView', 'approvals')"
                    @click="if (isMobile) sidebarOpen = false"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors"
                    :class="'{{ $activeView }}' === 'approvals' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60'"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12z" />
                        </svg>
                        Approvals Queue
                    </span>
                    @if ($this->pendingApprovalsCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-500 text-white animate-pulse">
                            {{ $this->pendingApprovalsCount }}
                        </span>
                    @endif
                @endif
                </button>

                <button
                    type="button"
                    wire:click="$set('activeView', 'corpus')"
                    @click="if (isMobile) sidebarOpen = false"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors"
                    :class="'{{ $activeView }}' === 'corpus' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60'"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        Regulatory Corpus
                    </span>
                    @if (! Auth::user()->isAdmin())
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500">Qdrant</span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="$set('activeView', 'usage')"
                    @click="if (isMobile) sidebarOpen = false"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors"
                    :class="'{{ $activeView }}' === 'usage' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60'"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                        Spend & Governance
                    </span>
                    <span class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold">T3 Governor</span>
                </button>

                @if (Auth::user()->isAdmin())
                    <button
                        type="button"
                        wire:click="$set('activeView', 'document-upload')"
                        @click="if (isMobile) sidebarOpen = false"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors"
                        :class="'{{ $activeView }}' === 'document-upload' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60'"
                    >
                        <span class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            Document Upload
                        </span>
                    </button>
                @endif
            </div>

            @if (! Auth::user()->isAdmin())
            <!-- Search Consultations -->
            <div class="px-3 pt-3">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="searchQuery"
                        placeholder="Search consultations..."
                        class="w-full rounded-xl py-1.5 pl-8 pr-3 text-xs bg-gray-100 dark:bg-[#141414] border-0 text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
            </div>

            <!-- Recent Consultations List -->
            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-1">
                <div class="px-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    Recent Consultations
                </div>

                @forelse ($this->cases as $c)
                    <div
                        wire:click="selectCase({{ $c->id }})"
                        @click="if (isMobile) sidebarOpen = false"
                        class="group relative flex items-center justify-between p-2 rounded-xl text-xs cursor-pointer transition-colors"
                        :class="{{ $activeCaseId === $c->id ? 'true' : 'false' }} ? 'bg-gray-100 dark:bg-[#282a2c] text-gray-900 dark:text-white font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1f20] hover:text-gray-900 dark:hover:text-gray-200'"
                    >
                        <div class="min-w-0 flex-1 pr-2">
                            <div class="flex items-center gap-1.5">
                                @if ($c->status === 'approved')
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                @elseif ($c->status === 'rejected')
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 flex-shrink-0"></span>
                                @else
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse flex-shrink-0"></span>
                                @endif
                                <span class="truncate">{{ $c->title }}</span>
                            </div>
                            <span class="text-[10px] text-gray-400 pl-3 block">{{ $c->created_at?->diffForHumans() }}</span>
                        </div>

                        <button
                            type="button"
                            wire:click.stop="deleteCase({{ $c->id }})"
                            wire:confirm="Delete this consultation record?"
                            class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-rose-500 transition-opacity"
                            title="Delete"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center text-xs text-gray-400 dark:text-gray-500">
                        No consultations found.
                    </div>
                @endforelse
            </div>

            @endif

            <!-- Bottom User Profile Card & Theme Toggle -->
            <div class="absolute bottom-0 left-0 w-full p-3 border-t space-y-2.5" style="border-color: var(--border-primary);">
                @if (! Auth::user()->isAdmin())
                <!-- Token Budget Mini Widget -->
                <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-[#141414] border border-gray-200/80 dark:border-gray-800/80">
                    <div class="flex items-center justify-between text-[11px] mb-1 font-medium">
                        <span class="text-gray-500 dark:text-gray-400">Monthly Tokens</span>
                        <span class="text-gray-800 dark:text-gray-200 font-mono">{{ number_format($this->userBudget?->available_tokens ?? 241580) }} left</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-1.5 rounded-full" style="width: {{ 100 - ($this->userBudget?->percentage_used ?? 3.4) }}%"></div>
                    </div>
                </div>
                @endif

                <!-- User Bar -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-medium text-xs flex items-center justify-center flex-shrink-0 shadow-xs">
                            {{ strtoupper(Auth::user()->name[0] ?? 'O') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold truncate text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</p>
                            <span class="text-[10px] text-gray-400 capitalize">{{ Auth::user()->role ?? 'Officer' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <!-- Theme Toggle Button -->
                        <button
                            type="button"
                            onclick="toggleAppTheme()"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            title="Toggle Appearance"
                        >
                            <svg class="w-4 h-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                            <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </button>

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                title="Sign out"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Area -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative min-w-0">
        <!-- Minimal Top Navigation Bar -->
        <header
            class="h-14 border-b px-3 sm:px-6 flex items-center justify-between flex-shrink-0 z-20"
            style="background-color: var(--surface); border-color: var(--border-primary);"
        >
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button
                    type="button"
                    @click="sidebarOpen = !sidebarOpen"
                    class="p-2 -ml-1 sm:ml-0 rounded-xl text-gray-500 hover:text-gray-800 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors flex-shrink-0"
                    title="Toggle Sidebar"
                    aria-label="Toggle navigation drawer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Active View Breadcrumb -->
                <div class="flex items-center gap-1.5 sm:gap-2 text-xs min-w-0">
                    <span class="font-medium text-gray-400 hidden xs:inline">Copilot</span>
                    <span class="text-gray-300 dark:text-gray-700 hidden xs:inline">/</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100 truncate max-w-[140px] xs:max-w-[200px] sm:max-w-xs md:max-w-md">
                        @if ($activeView === 'home')
                            Overview
                        @elseif ($activeView === 'corpus')
                            Regulatory Corpus
                        @elseif ($activeView === 'approvals')
                            Approvals Queue
                        @elseif ($activeView === 'usage')
                            Spend & Governance
                        @elseif ($activeView === 'document-upload')
                            Document Upload
                        @else
                            {{ $this->activeCase ? $this->activeCase->title : 'New Consultation' }}
                        @endif
                    </span>
                </div>
            </div>

            <!-- Right Telemetry & Status Badges -->
            <div class="flex items-center gap-1.5 sm:gap-2.5 flex-shrink-0">
                <!-- Model Tier Selector Pill -->
                @if (! Auth::user()->isAdmin())
                <div class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-[#1e1f20] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    <span class="hidden sm:inline">Gemini 2.5 Flash</span>
                    <span class="text-gray-400 text-[10px] font-mono">Reasoning</span>
                </div>

                <!-- Remaining Quota (Hidden on very narrow mobile, visible from sm up) -->
                <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                    <span>{{ number_format($this->userBudget?->available_tokens ?? 241580) }} tokens</span>
                </div>
                @endif

                <!-- Quick Theme Toggle for Canvas -->
                <button
                    type="button"
                    onclick="toggleAppTheme()"
                    class="p-2 rounded-xl text-gray-500 hover:text-gray-800 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    title="Toggle Theme"
                >
                    <svg class="w-4 h-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="flex-1 overflow-hidden relative flex flex-col min-w-0">
            @php($allowedViews = ['consultation', 'approvals', 'corpus', 'usage', 'document-upload', 'home'])
            @php($resolvedView = in_array($activeView, $allowedViews, true) ? $activeView : 'consultation')

            @if ($resolvedView === 'home' && Auth::user()->isAdmin())
                @include('livewire.copilot.home')
            @elseif ($resolvedView === 'document-upload' && Auth::user()->isAdmin())
                <livewire:document-upload />
            @elseif ($resolvedView === 'document-upload' || $resolvedView === 'home')
                @include('livewire.copilot.consultation')
            @else
                @include('livewire.copilot.' . $resolvedView)
            @endif
        </main>
    </div>

    <!-- Edit Response Modal -->
    <x-modal wire:model="isEditModalOpen" title="Edit Official Citizen Draft Response" maxWidth="2xl">
        <form wire:submit.prevent="saveEditedApproval" class="space-y-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                You can adjust, clarify, or refine the draft guidance notice. Your modified text will be stored as the authorized official record.
            </p>

            <textarea
                wire:model="editedResponseText"
                rows="8"
                class="w-full rounded-2xl p-3 sm:p-4 text-xs sm:text-sm font-mono bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            ></textarea>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                <x-button variant="ghost" size="sm" type="button" wire:click="$set('isEditModalOpen', false)">
                    Cancel
                </x-button>
                <x-button variant="success" size="sm" type="submit">
                    Save Changes & Approve
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Reject Modal -->
    <x-modal wire:model="isRejectModalOpen" title="Reject Proposed Draft Response" maxWidth="md">
        <form wire:submit.prevent="submitRejection" class="space-y-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                State the reason for refusing the generated draft response. This feedback will be audited for regulatory evaluation.
            </p>

            <textarea
                wire:model="rejectionComment"
                rows="4"
                placeholder="Explain why this response cannot be approved (e.g. conflicting law, missing verification)..."
                class="w-full rounded-2xl p-3 text-xs sm:text-sm bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
            ></textarea>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                <x-button variant="ghost" size="sm" type="button" wire:click="$set('isRejectModalOpen', false)">
                    Cancel
                </x-button>
                <x-button variant="danger" size="sm" type="submit">
                    Confirm Rejection
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Citation Inspector Modal -->
    <x-modal wire:model="isCitationModalOpen" title="Regulatory Citation Evidence" maxWidth="xl">
        @if ($selectedCitation)
            <div class="space-y-4">
                <div class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/60">
                    <div class="flex items-center justify-between text-xs text-blue-900 dark:text-blue-200 font-semibold mb-1">
                        <span class="truncate mr-2">{{ $selectedCitation['document'] ?? 'Official Regulatory Document' }}</span>
                        <x-badge variant="info" size="sm">Score: {{ round(($selectedCitation['score'] ?? 0.9) * 100) }}%</x-badge>
                    </div>
                    <p class="text-xs text-blue-700 dark:text-blue-300 font-medium">
                        {{ $selectedCitation['section'] ?? '' }} • {{ $selectedCitation['clause'] ?? '' }}
                    </p>
                </div>

                <div>
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Exact Extracted Corpus Chunk:</h4>
                    <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50 dark:bg-[#141414] border border-gray-200/80 dark:border-gray-800 text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-serif italic max-h-48 overflow-y-auto">
                        "{{ $selectedCitation['excerpt'] ?? 'Evidence text extracted from knowledge base.' }}"
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div>Chunk ID: <span class="font-mono text-gray-700 dark:text-gray-300">{{ $selectedCitation['id'] ?? 'N/A' }}</span></div>
                    <div>Version: <span class="font-mono text-gray-700 dark:text-gray-300">{{ $selectedCitation['version'] ?? 'Current' }}</span></div>
                </div>

                <div class="flex justify-end pt-3">
                    <x-button variant="secondary" size="sm" type="button" wire:click="$set('isCitationModalOpen', false)">
                        Close
                    </x-button>
                </div>
            </div>
        @endif
    </x-modal>
</div>

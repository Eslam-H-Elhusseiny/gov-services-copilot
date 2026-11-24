                <!-- Active Case Consultation Stream / Empty State -->
                <div class="flex-1 overflow-y-auto px-3 py-4 sm:px-6 lg:px-8 pb-44 sm:pb-48">
                    @if ($this->activeCase)
                        <div class="max-w-4xl mx-auto space-y-6 sm:space-y-8 animate-in">
                            <!-- Case Meta Header Banner -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 sm:pb-6 border-b border-gray-100 dark:border-gray-800/80">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-mono text-gray-400 dark:text-gray-500">CASE #{{ str_pad($this->activeCase->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                        @if ($this->activeCase->status === 'approved')
                                            <x-badge variant="success" size="sm" dot>Approved</x-badge>
                                        @elseif ($this->activeCase->status === 'rejected')
                                            <x-badge variant="danger" size="sm">Rejected</x-badge>
                                        @else
                                            <x-badge variant="warning" size="sm" dot>Awaiting Approval</x-badge>
                                        @endif
                                    </div>
                                    <h2 class="text-lg sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 truncate">
                                        {{ $this->activeCase->title }}
                                    </h2>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-center">
                                    <button
                                        type="button"
                                        wire:click="newCase"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        New Query
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="deleteCase({{ $this->activeCase->id }})"
                                        wire:confirm="Are you sure you want to delete this consultation?"
                                        class="p-1.5 rounded-xl text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                        title="Delete case"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Messages Thread -->
                            @foreach ($this->activeCase->messages as $msg)
                                @if ($msg->sender_type === 'officer')
                                    <!-- Officer Message Bubble -->
                                    <div class="flex gap-2.5 sm:gap-4 items-start">
                                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-blue-600 text-white font-medium text-xs flex items-center justify-center flex-shrink-0 shadow-xs">
                                            {{ strtoupper(Auth::user()->name[0] ?? 'O') }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</span>
                                                <span class="text-[10px] sm:text-[11px] text-gray-400">{{ $msg->created_at?->format('h:i A') }}</span>
                                                <span class="text-[9px] sm:text-[10px] uppercase tracking-wider px-1.5 py-0.2 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">Officer Input</span>
                                            </div>
                                            <div class="p-3 sm:p-4 rounded-2xl bg-gray-50/80 dark:bg-[#1a1b1c] border border-gray-100 dark:border-gray-800/80 text-xs sm:text-sm text-gray-800 dark:text-gray-200">
                                                {{ $msg->content }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- AI Assistant Copilot Message -->
                                    <div class="flex gap-2.5 sm:gap-4 items-start">
                                        <!-- Authoritative Shield Avatar -->
                                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-3 sm:space-y-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1.5 sm:gap-2">
                                                    <span class="text-xs font-semibold text-gray-900 dark:text-gray-100">Gov Services Copilot</span>
                                                    <span class="text-[9px] sm:text-[10px] uppercase font-mono tracking-wider px-1.5 sm:px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200/50 dark:border-blue-800/40">Multi-Agent RAG</span>
                                                    <span class="text-[10px] sm:text-[11px] text-gray-400 hidden xs:inline">{{ $msg->created_at?->format('h:i A') }}</span>
                                                </div>
                                            </div>

                                            <!-- Multi-Agent Reasoning Chain -->
                                            <div x-data="{ expanded: false }" class="rounded-xl border border-gray-200/80 dark:border-gray-800/80 bg-gray-50/50 dark:bg-[#18191a] overflow-hidden text-xs">
                                                <button
                                                    type="button"
                                                    @click="expanded = !expanded"
                                                    class="w-full px-3 py-2 flex items-center justify-between text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                                                >
                                                    <span class="flex items-center gap-1.5 sm:gap-2 font-medium text-[11px] sm:text-xs">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                                        <span class="truncate">Multi-Agent Reasoning Chain (3 Agents Complete)</span>
                                                    </span>
                                                    <svg class="w-4 h-4 transition-transform duration-200 flex-shrink-0 ml-1" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>

                                                <div x-show="expanded" x-collapse class="px-3 pb-3 pt-1 border-t border-gray-200/60 dark:border-gray-800/60 space-y-2 text-gray-600 dark:text-gray-400 text-xs">
                                                    <div class="flex items-start gap-2">
                                                        <span class="font-mono text-blue-600 dark:text-blue-400 font-semibold">01.</span>
                                                        <div>
                                                            <strong class="text-gray-800 dark:text-gray-200">Eligibility Identifier:</strong> Analyzed citizen conditions, resolved statutory qualification criteria, and verified absence of explicit legal disqualifiers.
                                                        </div>
                                                    </div>
                                                    <div class="flex items-start gap-2">
                                                        <span class="font-mono text-blue-600 dark:text-blue-400 font-semibold">02.</span>
                                                        <div>
                                                            <strong class="text-gray-800 dark:text-gray-200">Procedure Resolver:</strong> Retrieved required documentation checklist, executed deterministic fee arithmetic, and computed statutory processing deadlines.
                                                        </div>
                                                    </div>
                                                    <div class="flex items-start gap-2">
                                                        <span class="font-mono text-blue-600 dark:text-blue-400 font-semibold">03.</span>
                                                        <div>
                                                            <strong class="text-gray-800 dark:text-gray-200">Response Drafter:</strong> Synthesized structured official guidance notice and tagged source citations traceable to verified regulatory chunks.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Authoritative Response Card with Markdown Body -->
                                            <div class="p-4 sm:p-6 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200/90 dark:border-gray-800 shadow-xs space-y-4 sm:space-y-5">
                                                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-800 dark:text-gray-200 leading-relaxed font-normal text-xs sm:text-sm overflow-x-auto">
                                                    {!! Str::markdown($msg->content) !!}
                                                </div>

                                                <!-- Source Citations Section -->
                                                @if (! empty($msg->citations))
                                                    <div class="pt-3 sm:pt-4 border-t border-gray-100 dark:border-gray-800/80">
                                                        <div class="text-[11px] sm:text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                                            </svg>
                                                            Verified Source Citations:
                                                        </div>

                                                        <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                                            @foreach ($msg->citations as $citation)
                                                                <button
                                                                    type="button"
                                                                    wire:click="inspectCitation({{ json_encode($citation) }})"
                                                                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 rounded-xl text-[11px] sm:text-xs font-medium bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/50 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors max-w-full truncate"
                                                                >
                                                                    <svg class="w-3 h-3 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                                                    </svg>
                                                                    <span class="truncate">{{ $citation['clause'] ?? $citation['document'] }}</span>
                                                                    <span class="text-[10px] opacity-75 font-mono">({{ round(($citation['score'] ?? 0.9) * 100) }}%)</span>
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Human Approval Gate Bar (Domain D4 Mandatory Action Bar) -->
                                                @php
                                                    $run = $this->activeCase->workflowRuns->first();
                                                @endphp
                                                @if ($run)
                                                    <div class="mt-4 sm:mt-5 pt-3 sm:pt-4 border-t border-gray-100 dark:border-gray-800">
                                                        @if ($run->current_state === 'PENDING_APPROVAL')
                                                            <div class="p-3.5 sm:p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                                <div>
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse flex-shrink-0"></span>
                                                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-900 dark:text-amber-200">Official Decision Gate Required</h4>
                                                                    </div>
                                                                    <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5">As the presiding officer, you must verify and authorize this response before it becomes consequential.</p>
                                                                </div>

                                                                <div class="flex flex-wrap sm:flex-nowrap items-center gap-1.5 sm:gap-2 flex-shrink-0 w-full sm:w-auto">
                                                                    <x-button
                                                                        variant="success"
                                                                        size="sm"
                                                                        class="flex-1 sm:flex-none text-xs"
                                                                        wire:click="approveResponse({{ $run->id }})"
                                                                        wire:loading.attr="disabled"
                                                                    >
                                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                                        </svg>
                                                                        Approve
                                                                    </x-button>

                                                                    <x-button
                                                                        variant="secondary"
                                                                        size="sm"
                                                                        class="flex-1 sm:flex-none text-xs"
                                                                        wire:click="openEditModal({{ $run->id }})"
                                                                    >
                                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                                        </svg>
                                                                        Edit & Approve
                                                                    </x-button>

                                                                    <x-button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        class="flex-1 sm:flex-none text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                                                        wire:click="openRejectModal({{ $run->id }})"
                                                                    >
                                                                        Reject
                                                                    </x-button>
                                                                </div>
                                                            </div>
                                                        @elseif ($run->current_state === 'APPROVED')
                                                            <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center justify-between">
                                                                <div class="flex items-center gap-2.5">
                                                                    <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center flex-shrink-0">
                                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-200">Official Decision Approved & Dispatched</span>
                                                                        <p class="text-[10px] sm:text-[11px] text-emerald-700 dark:text-emerald-400">Authenticated by {{ Auth::user()->name }} • Immutable audit trail logged</p>
                                                                    </div>
                                                                </div>
                                                                <x-badge variant="success" size="sm">Approved</x-badge>
                                                            </div>
                                                        @elseif ($run->current_state === 'REJECTED')
                                                            <div class="p-3.5 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/80 dark:border-rose-800/60 flex items-center justify-between">
                                                                <div class="flex items-center gap-2.5">
                                                                    <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center flex-shrink-0">
                                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <span class="text-xs font-semibold text-rose-900 dark:text-rose-200">Draft Rejected by Officer</span>
                                                                        <p class="text-[10px] sm:text-[11px] text-rose-700 dark:text-rose-400">Response halted and logged for policy re-evaluation</p>
                                                                    </div>
                                                                </div>
                                                                <x-badge variant="danger" size="sm">Rejected</x-badge>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <!-- Empty State / Clean Greeting -->
                        <div class="max-w-3xl mx-auto h-full flex flex-col items-center justify-center text-center px-2 sm:px-4 py-8 animate-in">
                            <!-- Authoritative Shield Emblem Hero -->
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 text-white flex items-center justify-center mb-4 sm:mb-5 shadow-lg shadow-blue-500/15">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>

                            <h1 class="text-xl sm:text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 mb-2">
                                Good day, {{ Auth::user()->name }}
                            </h1>
                            <p class="text-xs sm:text-base text-gray-500 dark:text-gray-400 max-w-lg mb-6 sm:mb-10 leading-relaxed font-normal px-2">
                                Enter citizen circumstances to identify service eligibility, resolve statutory procedures, and prepare verifiable official draft responses.
                            </p>

                            <!-- Prompt Suggestion Cards  -->
                            {{-- <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5 w-full text-left">
                                <button
                                    type="button"
                                    wire:click="applyPrompt('Citizen Ahmed Mahmoud (Age: 67, Retired Pensioner) lost his national identity card while traveling. He requests an expedited replacement and asks if pension recipients are eligible for statutory fee exemptions under Civil Affairs regulations.')"
                                    class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200/80 dark:border-gray-800/80 hover:border-blue-500/50 dark:hover:border-blue-500/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between"
                                >
                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1 flex items-center justify-between">
                                        <span>National ID Card Replacement</span>
                                        <span class="text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">↗</span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        Verify loss procedures, replacement documents, and senior citizen fee exemptions.
                                    </p>
                                </button>

                                <button
                                    type="button"
                                    wire:click="applyPrompt('Citizen wishes to obtain a new commercial shop license for a retail grocery store of 85 sqm. Requesting prerequisite building safety certificates, fees, and processing time limits under Law 154/2019.')"
                                    class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200/80 dark:border-gray-800/80 hover:border-blue-500/50 dark:hover:border-blue-500/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between"
                                >
                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1 flex items-center justify-between">
                                        <span>Commercial Shop Licensing</span>
                                        <span class="text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">↗</span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        Resolve building safety requisites, municipal inspection fees, and statutory timelines.
                                    </p>
                                </button>

                                <button
                                    type="button"
                                    wire:click="applyPrompt('Widowed citizen with two school-aged dependents inquiring about eligibility criteria for social solidarity monthly income assistance, required civil records, and application review office.')"
                                    class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200/80 dark:border-gray-800/80 hover:border-blue-500/50 dark:hover:border-blue-500/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between"
                                >
                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1 flex items-center justify-between">
                                        <span>Social Solidarity Assistance</span>
                                        <span class="text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">↗</span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        Review qualification boundaries, household income ceilings, and civil document checklists.
                                    </p>
                                </button>

                                <button
                                    type="button"
                                    wire:click="applyPrompt('Citizen with mobility disability requesting guidelines on issuing an integrated service card, medical committee assessment procedures, and public transport discount eligibility.')"
                                    class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200/80 dark:border-gray-800/80 hover:border-blue-500/50 dark:hover:border-blue-500/40 hover:shadow-md transition-all duration-200 group flex flex-col justify-between"
                                >
                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 mb-1 flex items-center justify-between">
                                        <span>Disability Welfare Card</span>
                                        <span class="text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">↗</span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        Check medical committee review standards and statutory transport fee reductions.
                                    </p>
                                </button>
                            </div> --}}
                        </div>
                    @endif
                </div>

                <!-- Floating / Anchored Prompt Input Area -->
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-white via-white/95 to-transparent dark:from-[#131314] dark:via-[#131314]/95 dark:to-transparent pt-4 sm:pt-6 pb-3 sm:pb-6 px-3 sm:px-6 lg:px-8 pointer-events-none">
                    <div class="max-w-4xl mx-auto pointer-events-auto">
                        <form wire:submit.prevent="submitSituation" class="relative">
                            <div class="relative rounded-2xl sm:rounded-3xl bg-white dark:bg-[#1e1f20] border border-gray-300 dark:border-gray-700/80 shadow-lg shadow-gray-200/40 dark:shadow-black/40 p-2.5 sm:p-4 focus-within:border-blue-500 dark:focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all duration-200">
                                <textarea
                                    wire:model="situationInput"
                                    rows="1"
                                    placeholder="Enter citizen circumstances, requested government service, or regulatory inquiry..."
                                    class="w-full max-h-50 bg-transparent border-0 resize-none text-xs sm:text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500  leading-relaxed overflow-y-auto focus:outline-none focus:ring-0 focus:border-transparent focus-visible:outline-none focus-visible:ring-0"
                                    style="outline: none !important; box-shadow: none !important;"
                                    @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight,200) + 'px'"
                                    @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submitSituation(); }"
                                ></textarea>

                                <div class="flex items-center justify-between pt-1.5 sm:pt-2 border-t border-gray-100 dark:border-gray-800/80 mt-1 gap-2">
                                    <!-- Model Routing Tier Pill -->
                                    <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                                        <span class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                            <span class="truncate">Gemini 2.5 Flash</span>
                                            <span class="text-gray-400 hidden xs:inline">• Reasoning</span>
                                        </span>
                                        <span class="text-[11px] text-gray-400 hidden md:inline">• Budget-aware routing</span>
                                    </div>

                                    <!-- Action Submit Button -->
                                    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                                        @if (!empty($situationInput))
                                            <button
                                                type="button"
                                                wire:click="$set('situationInput', '')"
                                                class="p-1 sm:p-1.5 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                                                title="Clear"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif

                                        <button
                                            type="submit"
                                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white flex items-center justify-center transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-sm flex-shrink-0"
                                            wire:loading.attr="disabled"
                                            aria-label="Send message"
                                        >
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


                <!-- Official Decision & Approvals Queue View -->
                <div class="flex-1 overflow-y-auto px-3 py-6 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-4 sm:space-y-6">
                    <div>
                        <h2 class="text-lg sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                            Official Decision & Approvals Queue
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Under Domain D4 governance rules, no AI-generated draft response may be communicated to citizens without explicit officer validation.
                        </p>
                    </div>

                    <div class="space-y-3 sm:space-y-4">
                        @forelse ($this->pendingRuns as $pendingRun)
                            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-3 sm:space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-3">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <x-badge variant="warning" size="sm" dot>Pending Decision</x-badge>
                                            <span class="text-xs font-mono text-gray-400">Run: {{ Str::limit($pendingRun->run_uuid, 8) }}</span>
                                        </div>
                                        <h3 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $pendingRun->serviceCase->title ?? 'Citizen Query' }}
                                        </h3>
                                    </div>

                                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                                        <x-button variant="success" size="sm" class="flex-1 sm:flex-none text-xs" wire:click="approveResponse({{ $pendingRun->id }})">
                                            Approve
                                        </x-button>
                                        <x-button variant="secondary" size="sm" class="flex-1 sm:flex-none text-xs" wire:click="openEditModal({{ $pendingRun->id }})">
                                            Edit & Approve
                                        </x-button>
                                        <x-button variant="ghost" size="sm" class="flex-1 sm:flex-none text-xs text-rose-600 dark:text-rose-400" wire:click="openRejectModal({{ $pendingRun->id }})">
                                            Reject
                                        </x-button>
                                    </div>
                                </div>

                                <div class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-[#141414] p-3 rounded-xl border border-gray-200/80 dark:border-gray-800 font-mono line-clamp-3">
                                    {{ Str::limit(strip_tags($pendingRun->draft_response), 250) }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 sm:py-16 px-4 rounded-3xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800">
                                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Queue Clear</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                    All current AI responses have been reviewed and adjudicated.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>


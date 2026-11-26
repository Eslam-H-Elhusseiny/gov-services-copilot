                <!-- Regulatory Knowledge Corpus View (Qdrant) -->
                <div class="flex-1 overflow-y-auto px-3 py-6 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-4 sm:space-y-6">
                    <div>
                        <h2 class="text-lg sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                            Regulatory Knowledge Base @if (! Auth::user()->isAdmin())(Qdrant Vector Index)@endif
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Official gazetted laws, ministerial decrees, and procedural handbooks indexed with dense semantic embeddings.
                        </p>
                    </div>

                    @php($statusLabels = ['pending' => 'Pending', 'processing' => 'Processing', 'indexed' => 'Completed', 'failed' => 'Failed'])
                    @php($statusBadges = ['pending' => 'warning', 'processing' => 'info', 'indexed' => 'success', 'failed' => 'danger'])

                    @if (Auth::user()->isAdmin())
                    <!-- Failed Documents (Admin) -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Failed Documents</h3>
                            <x-badge variant="danger" size="sm">{{ $this->failedDocuments->count() }}</x-badge>
                        </div>

                        @forelse ($this->failedDocuments as $doc)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-gray-50 dark:bg-[#141414] border border-gray-200/80 dark:border-gray-800/80">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium truncate text-gray-900 dark:text-gray-100">
                                        {{ $doc->document?->filename }} <span class="text-gray-400">({{ $doc->version_label }})</span>
                                    </p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                                        {{ $statusLabels[$doc->status] ?? $doc->status }} — {{ \Illuminate\Support\Str::limit((string) $doc->failure_details, 80) }}
                                    </p>
                                </div>
                                <x-button wire:click="retryDocument({{ $doc->id }})" class="flex-shrink-0">Retry</x-button>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 dark:text-gray-400">No failed documents.</p>
                        @endforelse
                    </div>
                    @endif

                    @if (! Auth::user()->isAdmin())
                    <!-- Vector DB Status Card -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/80 dark:border-blue-800/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm">Q</div>
                            <div>
                                <h4 class="text-xs sm:text-sm font-semibold text-blue-900 dark:text-blue-100">Qdrant Vector Engine</h4>
                                <p class="text-[11px] sm:text-xs text-blue-700 dark:text-blue-300">Collection: <span class="font-mono">gov_regulations</span> • Dimensions: 1536 • Metric: Cosine</p>
                            </div>
                        </div>
                        <x-badge variant="success" size="sm" dot>Connected & Healthy</x-badge>
                    </div>
                    @endif

                    <!-- Uploaded Documents -->
                    <div class="space-y-3 sm:space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                            @forelse ($this->documents as $doc)
                                @php($latest = $doc->versions->first())
                                <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                        <span class="truncate mr-2">{{ $doc->filename }}</span>
                                        <x-badge :variant="$statusBadges[$latest?->status] ?? 'neutral'" size="sm">{{ $statusLabels[$latest?->status] ?? ($latest?->status ?? '—') }}</x-badge>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $latest?->version_label ?? '—' }} &middot; {{ $latest?->created_at?->diffForHumans() ?? '—' }} &middot; {{ number_format((int) ($latest?->chunk_count ?? 0)) }} chunks
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 md:col-span-2">
                                    No documents uploaded yet. Upload your first regulatory document to get started.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>


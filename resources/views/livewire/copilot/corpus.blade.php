                <!-- Regulatory Knowledge Corpus View (Qdrant) -->
                <div class="flex-1 overflow-y-auto px-3 py-6 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-4 sm:space-y-6">
                    <div>
                        <h2 class="text-lg sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                            Regulatory Knowledge Base (Qdrant Vector Index)
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Official gazetted laws, ministerial decrees, and procedural handbooks indexed with dense semantic embeddings.
                        </p>
                    </div>

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

                    <!-- Ingested Laws Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-2">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                <span class="truncate mr-2">Civil Affairs Law No. 143/1994</span>
                                <x-badge variant="info" size="sm">4 Chunks</x-badge>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Governs citizen civil identification, loss reports, replacement cards, and biometrics confirmation.
                            </p>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-2">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                <span class="truncate mr-2">Prime Ministerial Decree No. 1205/2023</span>
                                <x-badge variant="info" size="sm">3 Chunks</x-badge>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Comprehensive public service fee schedules, senior citizen subsidies, and pensioner exemptions.
                            </p>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-2">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                <span class="truncate mr-2">Commercial Shops Law No. 154/2019</span>
                                <x-badge variant="info" size="sm">4 Chunks</x-badge>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Commercial licensing standards, zoning requirements, Civil Defense clearance, and 90-day timeframes.
                            </p>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs space-y-2">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                <span class="truncate mr-2">Unified Public Services Protocol (842/2022)</span>
                                <x-badge variant="info" size="sm">3 Chunks</x-badge>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Standards of public delivery, statutory timelines, receipts, and administrative appeal channels.
                            </p>
                        </div>
                    </div>
                </div>


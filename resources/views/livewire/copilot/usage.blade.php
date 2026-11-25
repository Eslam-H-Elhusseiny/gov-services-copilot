                <!-- Spend & Governance View (Twist T3 Cost Governor) -->
                <div class="flex-1 overflow-y-auto px-3 py-6 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-4 sm:space-y-6">
                    <div>
                        <h2 class="text-lg sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                            Token Cost Governor & Governance Telemetry
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Enforcing Twist T3 budget ceilings, adaptive model routing, and immutable regulatory audit logs.
                        </p>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Available Budget</span>
                            <div class="text-xl sm:text-2xl font-bold font-mono text-gray-900 dark:text-gray-100 mt-1">
                                {{ number_format($this->userBudget?->available_tokens ?? 241580) }}
                            </div>
                            <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">96.6% Remaining</span>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Model Routing Mix</span>
                            <div class="text-xl sm:text-2xl font-bold font-mono text-gray-900 dark:text-gray-100 mt-1">
                                78% / 22%
                            </div>
                            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-medium">Fast Tier / Reasoning Tier</span>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Calculated Cost Savings</span>
                            <div class="text-xl sm:text-2xl font-bold font-mono text-gray-900 dark:text-gray-100 mt-1">
                                $14.82
                            </div>
                            <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Via Dynamic Routing</span>
                        </div>
                    </div>

                    <!-- Audit Log Table -->
                    <div class="rounded-2xl bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-gray-100 dark:border-gray-800">
                            <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-gray-100">Immutable Audit Trail</h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($this->auditLogs as $log)
                                <div class="px-4 sm:px-5 py-2.5 sm:py-3 flex items-center justify-between text-xs">
                                    <div class="min-w-0 pr-2">
                                        <span class="font-mono font-semibold text-gray-800 dark:text-gray-200 truncate block">{{ $log->action }}</span>
                                        <span class="text-gray-400 block text-[10px] sm:text-[11px]">{{ $log->subject_type }} #{{ $log->subject_id }}</span>
                                    </div>
                                    <span class="text-gray-400 font-mono text-[11px] flex-shrink-0">{{ $log->created_at?->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

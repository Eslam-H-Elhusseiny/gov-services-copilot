<div class="flex-1 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
    <div class="w-full max-w-2xl space-y-4">
        <div>
            <h1 class="text-xl font-semibold" style="color: var(--text-primary);">Welcome, {{ Auth::user()->name }}</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">
                This area is for managing regulatory documents and monitoring AI usage across the system.
                Use the sidebar to upload documents, review the regulatory corpus, and inspect spend &amp; governance.
            </p>
        </div>

        <x-card>
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div class="text-sm" style="color: var(--text-secondary);">
                    <p class="font-medium" style="color: var(--text-primary);">Administrator workspace</p>
                    <p class="mt-1">Upload regulatory PDFs/DOCX, manage the corpus, and monitor system-wide AI usage and governance.</p>
                </div>
            </div>
        </x-card>
    </div>
</div>
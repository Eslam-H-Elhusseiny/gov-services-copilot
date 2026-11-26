<div class="flex-1 flex items-center justify-center p-6 sm:p-6 overflow-y-auto">
    <div class="w-full max-w-lg space-y-6">
        <div>
            <h1 class="text-xl font-semibold" style="color: var(--text-primary);">Upload Regulatory Document</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">Upload a PDF or DOCX. The document will be processed and queued for review.</p>
        </div>

        @if ($feedback !== '')
            <x-alert :type="$feedbackType">{{ $feedback }}</x-alert>
        @endif

        <x-card>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-primary);">Document file</label>
                    <input
                        type="file"
                        wire:model="file"
                        accept=".pdf,.docx"
                        class="block w-full text-sm rounded-lg border p-2"
                        style="background-color: var(--bg-secondary); border-color: var(--border-primary); color: var(--text-primary);"
                    >
                    @error('file')
                        <p class="text-sm mt-1" style="color: var(--error);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button
                        wire:click="submitUpload"
                        wire:loading.attr="disabled"
                        wire:target="submitUpload"
                        :disabled="! $file"
                    >
                        <span wire:loading.remove wire:target="submitUpload">Upload</span>
                        <span wire:loading wire:target="submitUpload" class="">
                            <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Uploading...</span>
                        </span>
                    </x-button>
                    <span class="text-xs" style="color: var(--text-secondary);">PDF or DOCX &middot; up to {{ round(config('documents.max_upload_kb') / 1024, 1) }} MB</span>
                </div>
            </div>
        </x-card>
    </div>
</div>

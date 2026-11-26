<?php

namespace App\Livewire;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Document\DuplicateDocumentException;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public $file;

    public string $feedback = '';

    public string $feedbackType = 'success';

    public function mount(): void
    {
        Gate::authorize('create', Document::class);
    }

    public function submitUpload(DocumentRepository $repository): void
    {
        Gate::authorize('create', Document::class);

        $data = $this->validate([
            'file' => 'required|file|mimes:pdf,docx|max:'.config('documents.max_upload_kb'),
        ]);

        $file = $data['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, config('documents.allowed_extensions'), true)) {
            $this->feedbackType = 'error';
            $this->feedback = 'Unsupported file type.';

            return;
        }

        $disk = Storage::disk(config('documents.disk'));
        $relativePath = $disk->putFileAs('documents', $file, Str::uuid().'.'.$extension);

        if ($relativePath === false) {
            $this->feedbackType = 'error';
            $this->feedback = 'Could not store the uploaded file.';

            return;
        }

        try {
            $hash = hash_file('sha256', $disk->path($relativePath));
        } catch (Throwable) {
            $disk->delete($relativePath);
            $this->feedbackType = 'error';
            $this->feedback = 'Could not process the uploaded file.';

            return;
        }

        try {
            $result = $repository->createDocumentVersion(
                Auth::id(),
                basename(str_replace('\\', '/', $file->getClientOriginalName())),
                $hash,
                $relativePath,
            );
        } catch (DuplicateDocumentException) {
            $disk->delete($relativePath);
            $this->file = null;
            $this->feedbackType = 'warning';
            $this->feedback = 'This document has already been uploaded.';

            return;
        } catch (Throwable) {
            $disk->delete($relativePath);
            $this->file = null;
            $this->feedbackType = 'error';
            $this->feedback = 'Upload failed. Please try again.';

            return;
        }

        ProcessDocumentJob::dispatch($result['version_id']);

        $this->file = null;
        $this->feedbackType = 'success';
        $this->feedback = 'Document uploaded and queued for processing.';
    }

    public function render()
    {
        return view('livewire.document-upload');
    }
}

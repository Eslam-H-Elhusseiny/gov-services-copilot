<?php

namespace App\Jobs;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Document\IngestDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly int $versionId) {}

    public function handle(IngestDocument $ingest): void
    {
        $ingest->execute($this->versionId);
    }

    public function failed(?Throwable $exception): void
    {
        try {
            app(DocumentRepository::class)->updateStatus(
                $this->versionId,
                'failed',
                $this->safeMessage($exception),
            );
        } catch (Throwable $secondary) {
            logger()->error('Document job failed and the status update also failed.', [
                'version_id' => $this->versionId,
            ]);
        }
    }

    private function safeMessage(?Throwable $exception): string
    {
        $message = trim((string) preg_replace('/\s+/', ' ', $exception?->getMessage() ?? ''));

        return substr($message === '' ? 'Document processing failed.' : $message, 0, 500);
    }
}

<?php

namespace App\Application\Copilot\Document;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\TextChunker;
use App\Domain\Copilot\Document\TextCleaner;
use App\Domain\Copilot\Embedding\EmbeddingException;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Vector\VectorException;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use RuntimeException;
use Throwable;

final class IngestDocument
{
    public function __construct(
        private DocumentRepository $repository,
        private DocumentParser $parser,
        private TextChunker $chunker,
        private TextCleaner $cleaner,
        private EmbeddingProvider $embeddings,
        private VectorIndex $vectors,
        private int $chunkSize,
        private int $chunkOverlap,
    ) {}

    public function execute(int $versionId): void
    {
        $version = $this->repository->findVersion($versionId);

        if ($version === null) {
            throw new RuntimeException('Document version not found.');
        }

        if ($version['status'] === 'indexed') {
            return;
        }

        $this->repository->updateStatus($versionId, 'processing');
        $this->repository->clearChunks($versionId);

        try {
            $parsed = $this->cleaner->cleanParsed($this->parser->parse($version['file_path']));
            $chunks = $this->chunker->chunk($parsed, $this->chunkSize, $this->chunkOverlap);
        } catch (Throwable $e) {
            $this->markFailed($versionId, $e->getMessage() ?? 'Document processing failed.');

            return;
        }

        if ($chunks === []) {
            $this->markFailed($versionId, 'No extractable text');

            return;
        }

        try {
            foreach ($chunks as $chunk) {
                $chunkId = $this->repository->saveChunk($versionId, $chunk);
                $embedding = $this->embeddings->embed($chunk->content);

                $this->vectors->upsert(new VectorPoint(
                    $chunkId,
                    $embedding->vector,
                    [
                        'document_version_id' => $versionId,
                        'document_id' => $version['document_id'],
                    ],
                ));
            }
        } catch (Throwable $e) {
            $this->markFailed($versionId, $e->getMessage() ?? 'Document processing failed.');

            if ($e instanceof EmbeddingException || $e instanceof VectorException) {
                if (! $e->retryable) {
                    return;
                }
            }

            throw $e;
        }

        $this->repository->updateStatus($versionId, 'indexed', null, count($chunks), true);
    }

    private function markFailed(int $versionId, string $message): void
    {
        try {
            $this->repository->updateStatus($versionId, 'failed', $this->sanitize($message));
        } catch (Throwable) {
            // A secondary status-update failure must not mask the original exception.
        }
    }

    private function sanitize(string $message): string
    {
        $message = trim((string) preg_replace('/\s+/', ' ', $message));
        $message = mb_scrub($message, 'UTF-8');

        return mb_substr($message, 0, 500, 'UTF-8');
    }
}

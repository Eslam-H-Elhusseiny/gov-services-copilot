<?php

namespace App\Application\Copilot\Document\Contracts;

use App\Domain\Copilot\Document\TextChunk;

interface DocumentRepository
{
    /**
     * Create (or find) the logical document and a new version in one transaction.
     *
     * @return array{document_id: int, version_id: int}
     */
    public function createDocumentVersion(
        int $userId,
        string $filename,
        string $contentHash,
        string $relativePath
    ): array;

    /**
     * @return array{id: int, document_id: int, file_path: string, status: string}|null
     */
    public function findVersion(int $versionId): ?array;

    public function saveChunk(int $versionId, TextChunk $chunk): int;

    public function clearChunks(int $versionId): void;

    public function updateStatus(
        int $versionId,
        string $status,
        ?string $failure = null,
        ?int $chunkCount = null,
        bool $indexed = false
    ): void;
}

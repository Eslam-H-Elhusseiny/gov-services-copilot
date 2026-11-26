<?php

namespace App\Infrastructure\Copilot\Document;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Document\DuplicateDocumentException;
use App\Domain\Copilot\Document\TextChunk;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentDocumentRepository implements DocumentRepository
{
    public function __construct(private VectorIndex $vectors) {}

    public function createDocumentVersion(
        int $userId,
        string $filename,
        string $contentHash,
        string $relativePath
    ): array {
        try {
            return DB::transaction(function () use ($userId, $filename, $contentHash, $relativePath) {
                $document = Document::firstOrCreate(['filename' => $filename]);

                $version = DocumentVersion::create([
                    'document_id' => $document->id,
                    'version_label' => 'v'.($document->versions()->count() + 1),
                    'content_hash' => $contentHash,
                    'file_path' => $relativePath,
                    'uploaded_by' => $userId,
                    'status' => 'pending',
                    'created_at' => now(),
                ]);

                return ['document_id' => $document->id, 'version_id' => $version->id];
            });
        } catch (QueryException $e) {
            if (self::isDuplicateContentHash($e)) {
                throw new DuplicateDocumentException('A document with this content already exists.');
            }

            throw $e;
        }
    }

    public function findVersion(int $versionId): ?array
    {
        $version = DocumentVersion::find($versionId);

        if ($version === null) {
            return null;
        }

        return [
            'id' => $version->id,
            'document_id' => $version->document_id,
            'file_path' => $version->file_path,
            'status' => $version->status,
        ];
    }

    public function saveChunk(int $versionId, TextChunk $chunk): int
    {
        $model = DocumentChunk::create([
            'document_version_id' => $versionId,
            'chunk_index' => $chunk->index,
            'content' => $chunk->content,
            'page_number' => $chunk->pageNumber,
            'section' => $chunk->section,
            'clause' => $chunk->clause,
            'created_at' => now(),
        ]);

        return (int) $model->id;
    }

    public function clearChunks(int $versionId): void
    {
        $ids = DocumentChunk::where('document_version_id', $versionId)->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        foreach ($ids as $id) {
            $this->vectors->delete((int) $id);
        }

        DocumentChunk::whereIn('id', $ids)->delete();
    }

    public function updateStatus(
        int $versionId,
        string $status,
        ?string $failure = null,
        ?int $chunkCount = null,
        bool $indexed = false
    ): void {
        $version = DocumentVersion::find($versionId);

        if ($version === null) {
            return;
        }

        $attributes = ['status' => $status];

        if ($failure !== null) {
            $attributes['failure_details'] = $failure;
        }

        if ($chunkCount !== null) {
            $attributes['chunk_count'] = $chunkCount;
        }

        if ($indexed) {
            $attributes['indexed_at'] = now();
            $attributes['failure_details'] = null;
        }

        $version->forceFill($attributes)->save();
    }

    public static function isDuplicateContentHash(QueryException $e): bool
    {
        $code = $e->errorInfo[1] ?? null;

        return $code === 1062 && str_contains($e->getMessage(), 'content_hash');
    }
}

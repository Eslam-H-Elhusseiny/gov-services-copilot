<?php

namespace Tests\Feature\Document;

use App\Domain\Copilot\Document\TextChunk;
use App\Domain\Copilot\Vector\VectorException;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use App\Infrastructure\Copilot\Document\EloquentDocumentRepository;
use App\Models\DocumentChunk;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;
use Throwable;

class EloquentDocumentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function repo(?RecordingVectorIndex $vector = null): EloquentDocumentRepository
    {
        return new EloquentDocumentRepository($vector ?? new RecordingVectorIndex);
    }

    private function queryException(int $code, string $message): QueryException
    {
        $pdo = new PDOException($message);
        $pdo->errorInfo = ['23000', $code, $message];

        return new QueryException('testing', 'insert into document_versions ...', [], $pdo);
    }

    public function test_creates_first_version_v1(): void
    {
        $user = $this->user();

        $result = $this->repo()->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');

        $this->assertDatabaseHas('documents', ['id' => $result['document_id'], 'filename' => 'law.pdf']);
        $this->assertDatabaseHas('document_versions', [
            'id' => $result['version_id'],
            'document_id' => $result['document_id'],
            'version_label' => 'v1',
            'status' => 'pending',
        ]);
    }

    public function test_creates_subsequent_version_v2_for_same_filename(): void
    {
        $user = $this->user();
        $repo = $this->repo();

        $first = $repo->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');
        $second = $repo->createDocumentVersion($user->id, 'law.pdf', 'hash-2', 'documents/law.pdf');

        $this->assertSame($first['document_id'], $second['document_id']);
        $this->assertDatabaseHas('document_versions', ['id' => $second['version_id'], 'version_label' => 'v2']);
    }

    public function test_mysql_content_hash_violation_is_classified_as_duplicate(): void
    {
        $duplicate = $this->queryException(1062, "Duplicate entry 'abc' for key 'document_versions.content_hash'");
        $this->assertTrue(EloquentDocumentRepository::isDuplicateContentHash($duplicate));

        $otherKey = $this->queryException(1062, "Duplicate entry 'abc' for key 'document_versions.other'");
        $this->assertFalse(EloquentDocumentRepository::isDuplicateContentHash($otherKey));

        $otherCode = $this->queryException(19, 'UNIQUE constraint failed: document_versions.content_hash');
        $this->assertFalse(EloquentDocumentRepository::isDuplicateContentHash($otherCode));
    }

    public function test_unrelated_integrity_errors_are_not_misclassified(): void
    {
        $otherColumn = $this->queryException(1062, "Duplicate entry 'x' for key 'document_versions.other'");
        $this->assertFalse(EloquentDocumentRepository::isDuplicateContentHash($otherColumn));

        $foreignKey = $this->queryException(1452, 'Cannot add or update a child row: a foreign key constraint fails');
        $this->assertFalse(EloquentDocumentRepository::isDuplicateContentHash($foreignKey));
    }

    public function test_find_version_returns_expected_shape(): void
    {
        $user = $this->user();
        $created = $this->repo()->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');

        $version = $this->repo()->findVersion($created['version_id']);

        $this->assertSame(
            ['id', 'document_id', 'file_path', 'status'],
            array_keys($version),
        );
        $this->assertSame('documents/law.pdf', $version['file_path']);
        $this->assertSame('pending', $version['status']);
    }

    public function test_find_version_returns_null_for_missing(): void
    {
        $this->assertNull($this->repo()->findVersion(999999));
    }

    public function test_save_chunk_persists_metadata_and_returns_id(): void
    {
        $user = $this->user();
        $created = $this->repo()->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');

        $id = $this->repo()->saveChunk($created['version_id'], new TextChunk(0, 'chunk body', 2, 'Chapter 1', 'Article 1'));

        $this->assertDatabaseHas('document_chunks', [
            'id' => $id,
            'document_version_id' => $created['version_id'],
            'chunk_index' => 0,
            'content' => 'chunk body',
            'page_number' => 2,
            'section' => 'Chapter 1',
            'clause' => 'Article 1',
        ]);
    }

    public function test_clear_chunks_deletes_qdrant_before_mysql(): void
    {
        $user = $this->user();
        $vector = new RecordingVectorIndex;
        $repo = $this->repo($vector);
        $created = $repo->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');
        $repo->saveChunk($created['version_id'], new TextChunk(0, 'a', null, null, null));
        $repo->saveChunk($created['version_id'], new TextChunk(1, 'b', null, null, null));

        $vector->versionId = $created['version_id'];
        $repo->clearChunks($created['version_id']);

        $this->assertCount(2, $vector->deleted);
        $this->assertTrue($vector->chunksStillExistAtDelete);
        $this->assertSame(0, DocumentChunk::count());
    }

    public function test_clear_chunks_skips_qdrant_when_no_chunks(): void
    {
        $user = $this->user();
        $vector = new RecordingVectorIndex;
        $repo = $this->repo($vector);
        $created = $repo->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');

        $repo->clearChunks($created['version_id']);

        $this->assertSame([], $vector->deleted);
    }

    public function test_clear_chunks_preserves_mysql_when_qdrant_fails(): void
    {
        $user = $this->user();
        $vector = new RecordingVectorIndex;
        $vector->throw = new VectorException('qdrant', 'delete failed', null, true);
        $repo = $this->repo($vector);
        $created = $repo->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');
        $repo->saveChunk($created['version_id'], new TextChunk(0, 'a', null, null, null));

        try {
            $repo->clearChunks($created['version_id']);
            $this->fail('Expected vector exception.');
        } catch (VectorException) {
        }

        $this->assertSame(1, DocumentChunk::count());
    }

    public function test_update_status_updates_failure_and_chunk_count(): void
    {
        $user = $this->user();
        $created = $this->repo()->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');

        $this->repo()->updateStatus($created['version_id'], 'failed', 'bad input', 5);

        $this->assertDatabaseHas('document_versions', [
            'id' => $created['version_id'],
            'status' => 'failed',
            'failure_details' => 'bad input',
            'chunk_count' => 5,
        ]);
    }

    public function test_update_status_indexed_sets_timestamp_and_clears_failure(): void
    {
        $user = $this->user();
        $repo = $this->repo();
        $created = $repo->createDocumentVersion($user->id, 'law.pdf', 'hash-1', 'documents/law.pdf');
        $repo->updateStatus($created['version_id'], 'failed', 'bad input', 5);

        $repo->updateStatus($created['version_id'], 'indexed', null, 3, true);

        $version = DocumentVersion::find($created['version_id']);
        $this->assertSame('indexed', $version->status);
        $this->assertNull($version->failure_details);
        $this->assertSame(3, $version->chunk_count);
        $this->assertNotNull($version->indexed_at);
    }
}

final class RecordingVectorIndex implements VectorIndex
{
    public array $deleted = [];

    public ?Throwable $throw = null;

    public ?int $versionId = null;

    public bool $chunksStillExistAtDelete = true;

    public function ensureCollection(): void {}

    public function upsert(VectorPoint $point): void {}

    public function delete(int $pointId): void
    {
        if ($this->throw !== null) {
            throw $this->throw;
        }

        $this->deleted[] = $pointId;

        if ($this->versionId !== null) {
            $this->chunksStillExistAtDelete = DocumentChunk::where('document_version_id', $this->versionId)->exists();
        }
    }

    public function search(array $vector, int $limit = 5): array
    {
        return [];
    }

    public function isReady(): bool
    {
        return true;
    }
}

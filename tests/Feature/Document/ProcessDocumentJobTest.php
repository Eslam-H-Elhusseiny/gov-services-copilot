<?php

namespace Tests\Feature\Document;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Document\IngestDocument;
use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\ParsedDocument;
use App\Domain\Copilot\Document\TextChunk;
use App\Domain\Copilot\Document\TextChunker;
use App\Domain\Copilot\Document\TextCleaner;
use App\Domain\Copilot\Embedding\EmbeddingException;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Embedding\EmbeddingResult;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use App\Infrastructure\Copilot\Document\EloquentDocumentRepository;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new ProcessDocumentJob(1));
    }

    public function test_constructor_stores_version_id(): void
    {
        $this->assertSame(42, (new ProcessDocumentJob(42))->versionId);
    }

    public function test_handle_delegates_to_ingest_with_correct_id(): void
    {
        $repo = new RecordingDocumentRepository(['id' => 1, 'document_id' => 1, 'file_path' => 'x', 'status' => 'pending']);

        $job = new ProcessDocumentJob(7);
        $job->handle(new IngestDocument($repo, new FixedParser(new ParsedDocument('', [])), new TextChunker, new TextCleaner, new ThrowingEmbeddingProvider, new NullVectorIndex, 50, 10));

        $this->assertSame(7, $repo->findVersionArg);
    }

    public function test_handle_propagates_exceptions(): void
    {
        $repo = new RecordingDocumentRepository(['id' => 1, 'document_id' => 1, 'file_path' => 'x', 'status' => 'pending']);
        $parser = new FixedParser(new ParsedDocument('', [1 => str_repeat('a', 120)]));
        $embeddings = new ThrowingEmbeddingProvider(new EmbeddingException('fake', 'retry', null, true));

        $this->expectException(EmbeddingException::class);

        (new ProcessDocumentJob(7))->handle(new IngestDocument($repo, $parser, new TextChunker, new TextCleaner, $embeddings, new NullVectorIndex, 50, 10));
    }

    public function test_failed_marks_version_failed_in_database(): void
    {
        $this->app->instance(DocumentRepository::class, new EloquentDocumentRepository(new NullVectorIndex));

        $user = User::factory()->create(['role' => 'officer']);
        $document = Document::create(['filename' => 'law.pdf']);
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_label' => 'v1',
            'content_hash' => 'hash-1',
            'file_path' => 'documents/law.pdf',
            'uploaded_by' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        (new ProcessDocumentJob($version->id))->failed(new RuntimeException('boom'));

        $this->assertDatabaseHas('document_versions', [
            'id' => $version->id,
            'status' => 'failed',
            'failure_details' => 'boom',
        ]);
    }

    public function test_failed_resolves_repository_through_container(): void
    {
        $repo = new RecordingDocumentRepository;
        $this->app->instance(DocumentRepository::class, $repo);

        (new ProcessDocumentJob(9))->failed(new RuntimeException('boom'));

        $this->assertSame('failed', $repo->updates[0]['status']);
        $this->assertSame(9, $repo->updates[0]['versionId']);
    }

    public function test_failed_sanitizes_and_truncates_message(): void
    {
        $repo = new RecordingDocumentRepository;
        $this->app->instance(DocumentRepository::class, $repo);

        $long = 'line one'.str_repeat('x', 1000)."\nline two";
        (new ProcessDocumentJob(9))->failed(new RuntimeException($long));

        $stored = $repo->updates[0]['failure'];
        $this->assertLessThanOrEqual(500, strlen($stored));
        $this->assertStringNotContainsString("\n", $stored);
    }

    public function test_status_update_failure_does_not_mask_original(): void
    {
        $this->app->instance(DocumentRepository::class, new ThrowingDocumentRepository);

        try {
            (new ProcessDocumentJob(9))->failed(new RuntimeException('original'));
            $this->assertTrue(true);
        } catch (Throwable) {
            $this->fail('failed() must not rethrow a secondary status-update failure.');
        }
    }

    public function test_generic_exception_marks_failed_not_processing(): void
    {
        $repo = new RecordingDocumentRepository(['id' => 1, 'document_id' => 1, 'file_path' => 'x', 'status' => 'pending']);
        $parser = new FixedParser(new ParsedDocument('', [1 => str_repeat('a', 120)]));
        $embeddings = new ThrowingEmbeddingProvider(new RuntimeException('generic failure'));

        $ingest = new IngestDocument($repo, $parser, new TextChunker, new TextCleaner, $embeddings, new NullVectorIndex, 50, 10);

        try {
            $ingest->execute(1);
        } catch (RuntimeException) {
        }

        $last = $repo->updates[array_key_last($repo->updates)];
        $this->assertSame('failed', $last['status']);
    }

    public function test_all_attempts_failing_then_failed_sets_status(): void
    {
        $this->app->instance(DocumentRepository::class, new EloquentDocumentRepository(new NullVectorIndex));

        $user = User::factory()->create(['role' => 'officer']);
        $document = Document::create(['filename' => 'law.pdf']);
        $version = DocumentVersion::forceCreate([
            'document_id' => $document->id,
            'version_label' => 'v1',
            'content_hash' => 'hash-retry',
            'file_path' => 'documents/law.pdf',
            'uploaded_by' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $parser = new FixedParser(new ParsedDocument('', [1 => str_repeat('a', 120)]));
        $embeddings = new ThrowingEmbeddingProvider(new EmbeddingException('fake', 'retry', null, true));
        $ingest = new IngestDocument(new EloquentDocumentRepository(new NullVectorIndex), $parser, new TextChunker, new TextCleaner, $embeddings, new NullVectorIndex, 50, 10);

        $job = new ProcessDocumentJob($version->id);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $job->handle($ingest);
            } catch (EmbeddingException) {
            }
        }

        $job->failed(new EmbeddingException('fake', 'retry', null, true));

        $this->assertSame('failed', $version->fresh()->status);
        $this->assertDatabaseHas('document_versions', ['id' => $version->id, 'status' => 'failed']);
    }
}

final class NullVectorIndex implements VectorIndex
{
    public function ensureCollection(): void {}

    public function upsert(VectorPoint $point): void {}

    public function delete(int $pointId): void {}

    public function search(array $vector, int $limit = 5): array
    {
        return [];
    }

    public function isReady(): bool
    {
        return true;
    }
}

final class FixedParser implements DocumentParser
{
    public function __construct(private ParsedDocument $document) {}

    public function parse(string $relativePath): ParsedDocument
    {
        return $this->document;
    }
}

final class ThrowingEmbeddingProvider implements EmbeddingProvider
{
    public function __construct(private ?Throwable $throw = null) {}

    public function name(): string
    {
        return 'fake';
    }

    public function dimensions(): int
    {
        return 3;
    }

    public function embed(string $text): EmbeddingResult
    {
        if ($this->throw !== null) {
            throw $this->throw;
        }

        return new EmbeddingResult('fake', 'fake-model', 3, [0.1, 0.2, 0.3], null);
    }
}

final class RecordingDocumentRepository implements DocumentRepository
{
    public ?int $findVersionArg = null;

    public array $updates = [];

    public int $nextChunkId = 1;

    public function __construct(private ?array $version = null) {}

    public function createDocumentVersion(int $userId, string $filename, string $contentHash, string $relativePath): array
    {
        return ['document_id' => 1, 'version_id' => 1];
    }

    public function findVersion(int $versionId): ?array
    {
        $this->findVersionArg = $versionId;

        return $this->version;
    }

    public function saveChunk(int $versionId, TextChunk $chunk): int
    {
        return $this->nextChunkId++;
    }

    public function clearChunks(int $versionId): void {}

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void
    {
        $this->updates[] = ['versionId' => $versionId, 'status' => $status, 'failure' => $failure, 'chunkCount' => $chunkCount, 'indexed' => $indexed];
    }
}

final class ThrowingDocumentRepository implements DocumentRepository
{
    public function createDocumentVersion(int $userId, string $filename, string $contentHash, string $relativePath): array
    {
        return ['document_id' => 1, 'version_id' => 1];
    }

    public function findVersion(int $versionId): ?array
    {
        return null;
    }

    public function saveChunk(int $versionId, TextChunk $chunk): int
    {
        return 1;
    }

    public function clearChunks(int $versionId): void {}

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void
    {
        throw new RuntimeException('status update failed');
    }
}

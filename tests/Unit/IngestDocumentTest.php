<?php

namespace Tests\Unit;

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
use App\Domain\Copilot\Vector\VectorException;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class IngestDocumentTest extends TestCase
{
    private function useCase(FakeDocumentRepository $repo, FakeDocumentParser $parser, FakeEmbeddingProvider $embeddings, FakeVectorIndex $vectors, int $size = 50, int $overlap = 10): IngestDocument
    {
        return new IngestDocument($repo, $parser, new TextChunker, new TextCleaner, $embeddings, $vectors, $size, $overlap);
    }

    private function lastStatus(FakeDocumentRepository $repo): ?array
    {
        return $repo->statuses === [] ? null : $repo->statuses[array_key_last($repo->statuses)];
    }

    public function test_successful_ingestion_marks_indexed(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $embeddings = new FakeEmbeddingProvider;
        $vectors = new FakeVectorIndex;

        $this->useCase($repo, $parser, $embeddings, $vectors)->execute(1);

        $status = $this->lastStatus($repo);
        $this->assertSame('indexed', $status['status']);
        $this->assertSame(count($repo->savedChunks), $status['chunkCount']);
        $this->assertCount(count($repo->savedChunks), $vectors->points);
    }

    public function test_cleaned_content_reaches_chunk_persistence(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument("bad \x80\x94 text", []);
        $embeddings = new FakeEmbeddingProvider;
        $vectors = new FakeVectorIndex;

        $this->useCase($repo, $parser, $embeddings, $vectors)->execute(1);

        $this->assertNotEmpty($repo->savedChunks);
        $content = $repo->savedChunks[0]->content;
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'));
        $this->assertStringContainsString('€', $content);
        $this->assertStringContainsString('”', $content);
    }

    public function test_already_indexed_returns_without_work(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'indexed']);
        $parser = new FakeDocumentParser;

        $this->useCase($repo, $parser, new FakeEmbeddingProvider, new FakeVectorIndex)->execute(1);

        $this->assertSame(0, $parser->parseCalls);
        $this->assertFalse($repo->clearCalled);
        $this->assertSame([], $repo->statuses);
    }

    public function test_missing_version_fails_clearly(): void
    {
        $repo = new FakeDocumentRepository(null);
        $repo->version = null;

        $this->expectException(RuntimeException::class);
        $this->useCase($repo, new FakeDocumentParser, new FakeEmbeddingProvider, new FakeVectorIndex)->execute(1);
    }

    public function test_empty_document_marks_failed_without_retry(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', []);

        $this->useCase($repo, $parser, new FakeEmbeddingProvider, new FakeVectorIndex)->execute(1);

        $status = $this->lastStatus($repo);
        $this->assertSame('failed', $status['status']);
        $this->assertSame('No extractable text', $status['failure']);
    }

    public function test_chunk_ids_are_used_as_point_ids(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $vectors = new FakeVectorIndex;

        $this->useCase($repo, $parser, new FakeEmbeddingProvider, $vectors)->execute(1);

        $pointIds = array_map(fn (VectorPoint $p) => $p->id, $vectors->points);
        $this->assertSame(range(1, count($repo->savedChunks)), $pointIds);
    }

    public function test_payload_contains_only_source_identifiers(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $vectors = new FakeVectorIndex;

        $this->useCase($repo, $parser, new FakeEmbeddingProvider, $vectors)->execute(1);

        foreach ($vectors->points as $point) {
            $keys = array_keys($point->payload);
            sort($keys);
            $this->assertSame(['document_id', 'document_version_id'], $keys);
            $this->assertSame(7, $point->payload['document_id']);
            $this->assertSame(1, $point->payload['document_version_id']);
        }
    }

    public function test_retryable_embedding_exception_updates_failed_and_rethrows(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $embeddings = new FakeEmbeddingProvider;
        $embeddings->throw = new EmbeddingException('fake', 'temporary', null, true);

        try {
            $this->useCase($repo, $parser, $embeddings, new FakeVectorIndex)->execute(1);
            $this->fail('Expected retryable exception.');
        } catch (EmbeddingException) {
        }

        $status = $this->lastStatus($repo);
        $this->assertSame('failed', $status['status']);
        $this->assertFalse($status['indexed']);
    }

    public function test_retryable_vector_exception_updates_failed_and_rethrows(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $vectors = new FakeVectorIndex;
        $vectors->throw = new VectorException('qdrant', 'temporary', null, true);

        try {
            $this->useCase($repo, $parser, new FakeEmbeddingProvider, $vectors)->execute(1);
            $this->fail('Expected retryable exception.');
        } catch (VectorException) {
        }

        $status = $this->lastStatus($repo);
        $this->assertSame('failed', $status['status']);
        $this->assertFalse($status['indexed']);
    }

    public function test_partial_failure_never_marks_indexed(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $vectors = new FakeVectorIndex;
        $vectors->throw = new VectorException('qdrant', 'boom', null, false);

        try {
            $this->useCase($repo, $parser, new FakeEmbeddingProvider, $vectors)->execute(1);
        } catch (VectorException) {
        }

        foreach ($repo->statuses as $status) {
            $this->assertNotSame('indexed', $status['status']);
        }
        $this->assertSame('failed', $this->lastStatus($repo)['status']);
    }

    public function test_clear_chunks_runs_before_parsing(): void
    {
        $order = new OrderLog;
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'documents/x.pdf', 'status' => 'pending'], $order);
        $parser = new FakeDocumentParser($order);
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);

        $this->useCase($repo, $parser, new FakeEmbeddingProvider, new FakeVectorIndex)->execute(1);

        $this->assertSame(['clear', 'parse'], $order->items);
    }

    public function test_invalid_utf8_failure_message_is_sanitized(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'x', 'status' => 'pending']);
        $parser = new FakeDocumentParser;
        $parser->throw = new RuntimeException("bad \x80\x94");

        $this->useCase($repo, $parser, new FakeEmbeddingProvider, new FakeVectorIndex)->execute(1);

        $last = $repo->statuses[array_key_last($repo->statuses)];
        $this->assertSame('failed', $last['status']);
        $this->assertTrue(mb_check_encoding((string) $last['failure'], 'UTF-8'));
        $this->assertLessThanOrEqual(500, strlen((string) $last['failure']));
    }

    public function test_generic_save_exception_is_rethrown_and_marks_failed(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'x', 'status' => 'pending']);
        $repo->throwOnSave = true;
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);

        try {
            $this->useCase($repo, $parser, new FakeEmbeddingProvider, new FakeVectorIndex)->execute(1);
            $this->fail('Expected RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertSame('save failed', $e->getMessage());
        }

        $last = $repo->statuses[array_key_last($repo->statuses)];
        $this->assertSame('failed', $last['status']);
    }

    public function test_secondary_update_failure_does_not_mask_original(): void
    {
        $repo = new FakeDocumentRepository(['id' => 1, 'document_id' => 7, 'file_path' => 'x', 'status' => 'pending']);
        $repo->throwOnUpdate = true;
        $parser = new FakeDocumentParser;
        $parser->document = new ParsedDocument('', [1 => str_repeat('a', 120)]);
        $embeddings = new FakeEmbeddingProvider;
        $embeddings->throw = new RuntimeException('original');

        try {
            $this->useCase($repo, $parser, $embeddings, new FakeVectorIndex)->execute(1);
            $this->fail('Expected original RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertSame('original', $e->getMessage());
        }
    }
}

final class OrderLog
{
    public array $items = [];
}

final class FakeDocumentRepository implements DocumentRepository
{
    public ?array $version;

    public array $statuses = [];

    public array $calls = [];

    public bool $clearCalled = false;

    public int $nextChunkId = 1;

    public array $savedChunks = [];

    public bool $throwOnSave = false;

    public bool $throwOnUpdate = false;

    public function __construct(?array $version = null, private ?OrderLog $order = null)
    {
        $this->version = $version;
    }

    public function createDocumentVersion(int $userId, string $filename, string $contentHash, string $relativePath): array
    {
        return ['document_id' => 1, 'version_id' => 1];
    }

    public function findVersion(int $versionId): ?array
    {
        $this->calls[] = 'find';

        return $this->version;
    }

    public function saveChunk(int $versionId, TextChunk $chunk): int
    {
        if ($this->throwOnSave) {
            throw new RuntimeException('save failed');
        }

        $this->calls[] = 'save';
        $this->savedChunks[] = $chunk;

        return $this->nextChunkId++;
    }

    public function clearChunks(int $versionId): void
    {
        $this->calls[] = 'clear';
        $this->clearCalled = true;

        if ($this->order !== null) {
            $this->order->items[] = 'clear';
        }
    }

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void
    {
        if ($this->throwOnUpdate && $status === 'failed') {
            throw new RuntimeException('update failed');
        }

        $this->calls[] = 'status';
        $this->statuses[] = ['status' => $status, 'failure' => $failure, 'chunkCount' => $chunkCount, 'indexed' => $indexed];
    }
}

final class FakeDocumentParser implements DocumentParser
{
    public int $parseCalls = 0;

    public ?ParsedDocument $document = null;

    public ?Throwable $throw = null;

    public function __construct(private ?OrderLog $order = null) {}

    public function parse(string $relativePath): ParsedDocument
    {
        $this->parseCalls++;

        if ($this->order !== null) {
            $this->order->items[] = 'parse';
        }

        if ($this->throw !== null) {
            throw $this->throw;
        }

        return $this->document ?? new ParsedDocument('', []);
    }
}

final class FakeEmbeddingProvider implements EmbeddingProvider
{
    public ?Throwable $throw = null;

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

final class FakeVectorIndex implements VectorIndex
{
    public array $points = [];

    public ?Throwable $throw = null;

    public function ensureCollection(): void {}

    public function upsert(VectorPoint $point): void
    {
        if ($this->throw !== null) {
            throw $this->throw;
        }

        $this->points[] = $point;
    }

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

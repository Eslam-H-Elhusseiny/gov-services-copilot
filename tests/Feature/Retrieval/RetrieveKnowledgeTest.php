<?php

namespace Tests\Feature\Retrieval;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Retrieval\RetrieveKnowledge;
use App\Domain\Copilot\Document\TextChunk;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Embedding\EmbeddingResult;
use App\Domain\Copilot\Retrieval\KeywordSearch;
use App\Domain\Copilot\Retrieval\RrfFuser;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use App\Domain\Copilot\Vector\VectorSearchResult;
use Tests\TestCase;

class RetrieveKnowledgeTest extends TestCase
{
    private function useCase(FakeEmbeddingProvider $e, FakeVectorIndex $v, FakeKeywordSearch $k, FakeDocumentRepository $d): RetrieveKnowledge
    {
        return new RetrieveKnowledge($e, $v, $k, new RrfFuser, $d, 60);
    }

    public function test_dense_results_converted_with_one_based_ranks(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(10, 0.9, []), new VectorSearchResult(11, 0.8, [])]);
        $k = new FakeKeywordSearch([]);
        $d = new FakeDocumentRepository([
            10 => $this->row(10, 7, 1, 'A'),
            11 => $this->row(11, 7, 1, 'B'),
        ]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'query');

        $this->assertSame([10, 11], array_column($outcome->chunks, 'id'));
        $this->assertSame('query', $e->lastQuery);
        $this->assertSame([0.1, 0.2, 0.3], $v->lastVector);
        $this->assertSame(5, $v->lastLimit);
    }

    public function test_dense_and_keyword_are_fused(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(1, 0.9, [])]);
        $k = new FakeKeywordSearch([['id' => 1, 'score' => 0.8, 'rank' => 2]]);
        $d = new FakeDocumentRepository([1 => $this->row(1, 7, 1, 'A')]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertCount(1, $outcome->chunks);
        $this->assertSame('both', $outcome->chunks[0]->sources);
    }

    public function test_single_retriever_result_is_kept(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(1, 0.9, [])]);
        $k = new FakeKeywordSearch([]);
        $d = new FakeDocumentRepository([1 => $this->row(1, 7, 1, 'A')]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertSame('dense', $outcome->chunks[0]->sources);
    }

    public function test_missing_mysql_chunks_are_skipped(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(1, 0.9, []), new VectorSearchResult(2, 0.8, [])]);
        $k = new FakeKeywordSearch([]);
        $d = new FakeDocumentRepository([1 => $this->row(1, 7, 1, 'A')]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertSame([1], array_column($outcome->chunks, 'id'));
    }

    public function test_rrf_order_preserved_over_repository_order(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(1, 0.9, []), new VectorSearchResult(2, 0.8, [])]);
        $k = new FakeKeywordSearch([]);
        // Repository returns reverse order.
        $d = new FakeDocumentRepository([2 => $this->row(2, 7, 1, 'B'), 1 => $this->row(1, 7, 1, 'A')]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertSame([1, 2], array_column($outcome->chunks, 'id'));
        $this->assertSame([1, 2], array_column($outcome->chunks, 'rank'));
    }

    public function test_empty_results_insufficient_evidence(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([]);
        $k = new FakeKeywordSearch([]);
        $d = new FakeDocumentRepository([]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertFalse($outcome->sufficientEvidence);
        $this->assertSame([], $outcome->chunks);
    }

    public function test_multiple_documents_are_not_a_conflict(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(1, 0.9, []), new VectorSearchResult(2, 0.8, [])]);
        $k = new FakeKeywordSearch([]);
        $d = new FakeDocumentRepository([1 => $this->row(1, 7, 1, 'A'), 2 => $this->row(2, 8, 1, 'B')]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertFalse($outcome->potentialConflicts);
        $this->assertTrue($outcome->sufficientEvidence);
    }

    public function test_multiple_versions_of_same_document_is_potential_conflict(): void
    {
        $e = new FakeEmbeddingProvider;
        $v = new FakeVectorIndex([new VectorSearchResult(1, 0.9, []), new VectorSearchResult(2, 0.8, [])]);
        $k = new FakeKeywordSearch([]);
        $d = new FakeDocumentRepository([1 => $this->row(1, 7, 1, 'A'), 2 => $this->row(2, 7, 2, 'A2')]);

        $outcome = $this->useCase($e, $v, $k, $d)->handle(1, 'q');

        $this->assertTrue($outcome->potentialConflicts);
    }

    private function row(int $id, int $documentId, int $versionId, string $content): array
    {
        return [
            'id' => $id,
            'content' => $content,
            'document_id' => $documentId,
            'document_version_id' => $versionId,
            'filename' => 'law.pdf',
            'page_number' => 1,
            'section' => null,
            'clause' => null,
        ];
    }
}

final class FakeEmbeddingProvider implements EmbeddingProvider
{
    public string $lastQuery = '';

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
        $this->lastQuery = $text;

        return new EmbeddingResult('fake', 'fake-model', 3, [0.1, 0.2, 0.3], null);
    }
}

final class FakeVectorIndex implements VectorIndex
{
    public ?array $lastVector = null;

    public ?int $lastLimit = null;

    public function __construct(private array $results) {}

    public function ensureCollection(): void {}

    public function upsert(VectorPoint $point): void {}

    public function delete(int $pointId): void {}

    public function search(array $vector, int $limit = 5): array
    {
        $this->lastVector = $vector;
        $this->lastLimit = $limit;

        return $this->results;
    }

    public function isReady(): bool
    {
        return true;
    }
}

final class FakeKeywordSearch implements KeywordSearch
{
    public ?string $lastQuery = null;

    public ?int $lastLimit = null;

    public function __construct(private array $results) {}

    public function search(string $query, int $limit): array
    {
        $this->lastQuery = $query;
        $this->lastLimit = $limit;

        return $this->results;
    }
}

final class FakeDocumentRepository implements DocumentRepository
{
    public ?array $lastIds = null;

    public function __construct(private array $rows) {}

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

    public function findChunksByIds(array $ids): array
    {
        $this->lastIds = $ids;

        return array_values(array_intersect_key($this->rows, array_flip($ids)));
    }

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void {}
}

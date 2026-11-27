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
use App\Models\CaseMessage;
use App\Models\ServiceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RetrieveKnowledgeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function bindUseCase(array $vectors, array $keyword, array $rows): void
    {
        $this->app->instance(RetrieveKnowledge::class, new RetrieveKnowledge(
            new Step4Embedding,
            new Step4Vectors($vectors),
            new Step4Keyword($keyword),
            new RrfFuser,
            new Step4Docs($rows),
            60,
        ));
    }

    private function caseFor(User $user): ServiceCase
    {
        $case = ServiceCase::create(['user_id' => $user->id, 'title' => 'Case', 'situation' => 'governance', 'status' => 'pending_approval']);
        CaseMessage::create(['case_id' => $case->id, 'sender_type' => 'officer', 'content' => 'governance', 'created_at' => now()]);

        return $case;
    }

    public function test_successful_retrieval_displays_results_and_citations(): void
    {
        $user = $this->officer();
        $this->caseFor($user);
        $this->bindUseCase([new VectorSearchResult(1, 0.9, [])], [], [1 => $this->row(1)]);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->set('activeView', 'consultation')
            ->set('knowledgeQuery', 'governance')
            ->call('searchKnowledge')
            ->assertSet('retrievalSearched', true)
            ->assertSet('sufficientEvidence', true)
            ->assertSee('law.pdf')
            ->assertSee('Page 3')
            ->assertSee('Chapter 1')
            ->assertSee('Article 1')
            ->assertSee('AI response will be available after Agents implementation.');
    }

    public function test_insufficient_evidence_with_retrieved_chunks_displays_both(): void
    {
        $user = $this->officer();
        $this->caseFor($user);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->set('activeView', 'consultation')
            ->set('retrievalSearched', true)
            ->set('sufficientEvidence', false)
            ->set('retrievalResults', [$this->rowArray(1)])
            ->assertSee('law.pdf')
            ->assertSee('The retrieved evidence is insufficient for this query.');
    }

    public function test_empty_results_show_no_chunks_and_insufficient(): void
    {
        $user = $this->officer();
        $this->caseFor($user);
        $this->bindUseCase([], [], []);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->set('activeView', 'consultation')
            ->set('knowledgeQuery', 'governance')
            ->call('searchKnowledge')
            ->assertSet('sufficientEvidence', false)
            ->assertSee('No relevant references found')
            ->assertSee('The retrieved evidence is insufficient for this query.')
            ->assertSee('AI response will be available after Agents implementation.');
    }

    public function test_officer_and_admin_can_search(): void
    {
        $this->bindUseCase([new VectorSearchResult(1, 0.9, [])], [], [1 => $this->row(1)]);

        Livewire::actingAs($this->officer())
            ->test('copilot-chat')
            ->set('knowledgeQuery', 'governance')
            ->call('searchKnowledge')
            ->assertSet('retrievalSearched', true);

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('knowledgeQuery', 'governance')
            ->call('searchKnowledge')
            ->assertSet('retrievalSearched', true);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    private function row(int $id): array
    {
        return [
            'id' => $id,
            'content' => 'body',
            'document_id' => 7,
            'document_version_id' => 9,
            'filename' => 'law.pdf',
            'page_number' => 3,
            'section' => 'Chapter 1',
            'clause' => 'Article 1',
        ];
    }

    private function rowArray(int $id): array
    {
        return [
            'id' => $id,
            'content' => 'body',
            'document_id' => 7,
            'document_version_id' => 9,
            'filename' => 'law.pdf',
            'page_number' => 3,
            'section' => 'Chapter 1',
            'clause' => 'Article 1',
            'rrf_score' => 0.016,
            'rank' => 1,
            'sources' => 'dense',
        ];
    }

    public function test_idle_button_is_enabled_and_wired_to_search(): void
    {
        $html = Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'consultation')
            ->set('knowledgeQuery', 'governance')
            ->html();

        $this->assertDoesNotMatchRegularExpression('/<button[^>]*\sdisabled[\s>]/', $html);
        $this->assertStringContainsString('wire:target="searchKnowledge"', $html);
    }

    public function test_search_failure_dispatches_toast_without_exception(): void
    {
        $this->app->instance(RetrieveKnowledge::class, new RetrieveKnowledge(
            new Step4FailEmbedding,
            new Step4Vectors([]),
            new Step4Keyword([]),
            new RrfFuser,
            new Step4Docs([]),
            60,
        ));

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'consultation')
            ->set('knowledgeQuery', 'governance')
            ->call('searchKnowledge')
            ->assertDispatched('toast', type: 'error', message: 'Search failed. Please try again.');
    }
}

final class Step4Embedding implements EmbeddingProvider
{
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
        return new EmbeddingResult('fake', 'fake-model', 3, [0.1, 0.2, 0.3], null);
    }
}

final class Step4Vectors implements VectorIndex
{
    public function __construct(private array $results) {}

    public function ensureCollection(): void {}

    public function upsert(VectorPoint $point): void {}

    public function delete(int $pointId): void {}

    public function search(array $vector, int $limit = 5): array
    {
        return $this->results;
    }

    public function isReady(): bool
    {
        return true;
    }
}

final class Step4Keyword implements KeywordSearch
{
    public function __construct(private array $results) {}

    public function search(string $query, int $limit): array
    {
        return $this->results;
    }
}

final class Step4Docs implements DocumentRepository
{
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
        return array_values(array_intersect_key($this->rows, array_flip($ids)));
    }

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void {}
}

final class Step4FailEmbedding implements EmbeddingProvider
{
    public function name(): string
    {
        return 'fail';
    }

    public function dimensions(): int
    {
        return 3;
    }

    public function embed(string $text): EmbeddingResult
    {
        throw new \RuntimeException('embedding failed');
    }
}

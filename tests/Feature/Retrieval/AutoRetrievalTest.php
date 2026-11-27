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

class AutoRetrievalTest extends TestCase
{
    use RefreshDatabase;

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function bind(array $vectors, array $rows): void
    {
        AutoDocs::$calls = 0;
        $this->app->instance(RetrieveKnowledge::class, new RetrieveKnowledge(
            new AutoEmbedding,
            new AutoVectors($vectors),
            new AutoKeyword([]),
            new RrfFuser,
            new AutoDocs($rows),
            60,
        ));
    }

    public function test_opening_case_auto_retrieves_without_search(): void
    {
        $user = $this->officer();
        $case = ServiceCase::create(['user_id' => $user->id, 'title' => 'Case', 'situation' => 'governance question', 'status' => 'pending_approval']);
        CaseMessage::create(['case_id' => $case->id, 'sender_type' => 'officer', 'content' => 'governance question', 'created_at' => now()]);

        $this->bind([new VectorSearchResult(1, 0.9, [])], [1 => $this->row()]);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->assertSet('retrievalSearched', true)
            ->assertSet('sufficientEvidence', true)
            ->assertSee('law.pdf')
            ->assertSee('AI response will be available after Agents implementation.');
    }

    public function test_retrieval_not_repeated_on_unrelated_render(): void
    {
        $user = $this->officer();
        $case = ServiceCase::create(['user_id' => $user->id, 'title' => 'Case', 'situation' => 'q', 'status' => 'pending_approval']);
        CaseMessage::create(['case_id' => $case->id, 'sender_type' => 'officer', 'content' => 'q', 'created_at' => now()]);

        $this->bind([new VectorSearchResult(1, 0.9, [])], [1 => $this->row()]);

        $component = Livewire::actingAs($user)->test('copilot-chat');
        $this->assertSame(1, AutoDocs::$calls);

        $component->set('searchQuery', 'unrelated');
        $this->assertSame(1, AutoDocs::$calls);
    }

    public function test_empty_results_show_insufficient_and_placeholder(): void
    {
        $user = $this->officer();
        $case = ServiceCase::create(['user_id' => $user->id, 'title' => 'Case', 'situation' => 'q', 'status' => 'pending_approval']);
        CaseMessage::create(['case_id' => $case->id, 'sender_type' => 'officer', 'content' => 'q', 'created_at' => now()]);

        $this->bind([], []);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->assertSet('sufficientEvidence', false)
            ->assertSee('No relevant references found')
            ->assertSee('The retrieved evidence is insufficient for this query.');
    }

    private function row(): array
    {
        return [
            'id' => 1,
            'content' => 'body',
            'document_id' => 7,
            'document_version_id' => 9,
            'filename' => 'law.pdf',
            'page_number' => 3,
            'section' => 'Chapter 1',
            'clause' => 'Article 1',
        ];
    }

    public function test_new_case_clears_previous_retrieval(): void
    {
        $user = $this->officer();
        $this->bind([new VectorSearchResult(1, 0.9, [])], [1 => $this->row()]);
        $case = $this->caseWithMessage($user, 'governance');

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->assertSet('retrievalSearched', true)
            ->call('newCase')
            ->assertSet('retrievalSearched', false)
            ->assertSet('retrievalResults', [])
            ->assertDontSee('law.pdf');
    }

    public function test_switching_to_zero_result_case_clears_previous(): void
    {
        $user = $this->officer();
        $this->bind([new VectorSearchResult(1, 0.9, [])], [1 => $this->row()]);
        $caseA = $this->caseWithMessage($user, 'governance');
        $caseB = $this->caseWithMessage($user, 'unrelated');

        $component = Livewire::actingAs($user)->test('copilot-chat');
        $component->assertSet('retrievalSearched', true);

        // Rebind to a zero-result retrieval for the second case.
        $this->app->instance(RetrieveKnowledge::class, new RetrieveKnowledge(
            new AutoEmbedding, new AutoVectors([]), new AutoKeyword([]), new RrfFuser, new AutoDocs([]), 60,
        ));

        $component->call('selectCase', $caseB->id)
            ->assertSet('sufficientEvidence', false)
            ->assertSee('No relevant references found')
            ->assertDontSee('law.pdf');
    }

    private function caseWithMessage(User $user, string $text): ServiceCase
    {
        $case = ServiceCase::create(['user_id' => $user->id, 'title' => 'Case', 'situation' => $text, 'status' => 'pending_approval']);
        CaseMessage::create(['case_id' => $case->id, 'sender_type' => 'officer', 'content' => $text, 'created_at' => now()]);

        return $case;
    }
}

final class AutoEmbedding implements EmbeddingProvider
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
        return new EmbeddingResult('fake', 'm', 3, [0.1, 0.2, 0.3], null);
    }
}

final class AutoVectors implements VectorIndex
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

final class AutoKeyword implements KeywordSearch
{
    public function __construct(private array $results) {}

    public function search(string $query, int $limit): array
    {
        return $this->results;
    }
}

final class AutoDocs implements DocumentRepository
{
    public static int $calls = 0;

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
        self::$calls++;

        return array_values(array_intersect_key($this->rows, array_flip($ids)));
    }

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void {}
}

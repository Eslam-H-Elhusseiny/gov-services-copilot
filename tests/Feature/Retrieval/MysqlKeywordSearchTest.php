<?php

namespace Tests\Feature\Retrieval;

use App\Domain\Copilot\Retrieval\KeywordSearch;
use App\Infrastructure\Copilot\Retrieval\MysqlKeywordSearch;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MysqlKeywordSearchTest extends TestCase
{
    use RefreshDatabase;

    private function searcher(): KeywordSearch
    {
        return app(KeywordSearch::class);
    }

    public function test_blank_query_returns_empty(): void
    {
        $this->assertSame([], $this->searcher()->search('', 5));
    }

    public function test_whitespace_query_returns_empty(): void
    {
        $this->assertSame([], $this->searcher()->search('   ', 5));
    }

    public function test_non_mysql_driver_returns_empty(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->markTestSkipped('Non-MySQL driver test.');
        }

        $this->assertSame([], $this->searcher()->search('governance', 5));
    }

    public function test_binding_resolves_to_mysql_implementation(): void
    {
        $this->assertInstanceOf(MysqlKeywordSearch::class, app(KeywordSearch::class));
    }

    public function test_mysql_matching_returns_ids_desc_and_one_based_ranks(): void
    {
        if (DB::getDriverName() !== 'mysql' || getenv('MYSQL_FULLTEXT_TESTS') !== '1') {
            $this->markTestSkipped('MySQL FULLTEXT only (set MYSQL_FULLTEXT_TESTS=1).');
        }

        $version = $this->makeIndexedVersion();
        $low = $this->makeChunk($version->id, 'governance', 0);
        $high = $this->makeChunk($version->id, 'governance governance governance regulatory compliance', 1);

        $results = $this->searcher()->search('governance', 5);

        $this->assertNotEmpty($results);
        $this->assertSame($high->id, $results[0]['id']);
        $this->assertSame(1, $results[0]['rank']);
        $this->assertGreaterThanOrEqual($results[0]['score'], $results[1]['score']);
        $this->assertContains($low->id, array_column($results, 'id'));
    }

    public function test_mysql_limit_is_respected(): void
    {
        if (DB::getDriverName() !== 'mysql' || getenv('MYSQL_FULLTEXT_TESTS') !== '1') {
            $this->markTestSkipped('MySQL FULLTEXT only (set MYSQL_FULLTEXT_TESTS=1).');
        }

        $version = $this->makeIndexedVersion();
        $this->makeChunk($version->id, 'governance one', 0);
        $this->makeChunk($version->id, 'governance two', 1);
        $this->makeChunk($version->id, 'governance three', 2);

        $results = $this->searcher()->search('governance', 2);

        $this->assertCount(2, $results);
    }

    public function test_mysql_no_match_returns_empty(): void
    {
        if (DB::getDriverName() !== 'mysql' || getenv('MYSQL_FULLTEXT_TESTS') !== '1') {
            $this->markTestSkipped('MySQL FULLTEXT only (set MYSQL_FULLTEXT_TESTS=1).');
        }

        $version = $this->makeIndexedVersion();
        $this->makeChunk($version->id, 'unrelated content', 0);

        $this->assertSame([], $this->searcher()->search('zzzzzzzz', 5));
    }

    private function makeIndexedVersion(): DocumentVersion
    {
        $user = User::factory()->create(['role' => 'officer']);
        $document = Document::create(['filename' => 'law.pdf']);

        return DocumentVersion::forceCreate([
            'document_id' => $document->id,
            'version_label' => 'v1',
            'content_hash' => hash('sha256', \uniqid('', true)),
            'file_path' => 'documents/law.pdf',
            'uploaded_by' => $user->id,
            'status' => 'indexed',
            'created_at' => now(),
        ]);
    }

    private function makeChunk(int $versionId, string $content, int $index): DocumentChunk
    {
        return DocumentChunk::create([
            'document_version_id' => $versionId,
            'chunk_index' => $index,
            'content' => $content,
            'created_at' => now(),
        ]);
    }
}

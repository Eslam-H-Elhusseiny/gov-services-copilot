<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentVersion;
use App\Models\LlmCall;
use App\Models\ModelPricing;
use App\Models\ServiceCase;
use App\Models\User;
use App\Models\WorkflowRun;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataPersistenceFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function makeDocument(): Document
    {
        return Document::create(['filename' => 'law.pdf']);
    }

    private function makeVersion(Document $document, User $user): DocumentVersion
    {
        return DocumentVersion::create([
            'document_id' => $document->id,
            'version_label' => 'v1',
            'content_hash' => Str::uuid()->toString(),
            'file_path' => 'docs/law.pdf',
            'uploaded_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    private function makeChunk(DocumentVersion $version, int $index = 0): DocumentChunk
    {
        return DocumentChunk::create([
            'document_version_id' => $version->id,
            'chunk_index' => $index,
            'content' => 'chunk body',
            'page_number' => 1,
            'section' => 'Chapter 1',
            'clause' => 'Article 1',
            'created_at' => now(),
        ]);
    }

    private function makePricing(): ModelPricing
    {
        return ModelPricing::create([
            'provider' => 'groq',
            'model' => 'llama-3.3',
            'input_price_per_1k' => 0.5,
            'output_price_per_1k' => 1.5,
            'currency' => 'USD',
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
            'created_at' => now(),
        ]);
    }

    private function makeRun(User $user): WorkflowRun
    {
        $case = ServiceCase::create([
            'user_id' => $user->id,
            'title' => 'Case',
            'situation' => 'Situation',
            'status' => 'pending_approval',
        ]);

        return WorkflowRun::create([
            'case_id' => $case->id,
            'run_uuid' => (string) Str::uuid(),
            'run_type' => 'officer',
            'current_state' => 'RECEIVED',
            'created_at' => now(),
        ]);
    }

    public function test_document_has_many_versions(): void
    {
        $user = $this->makeUser();
        $document = $this->makeDocument();
        $version = $this->makeVersion($document, $user);

        $this->assertTrue($document->versions()->exists());
        $this->assertSame($version->id, $document->versions->first()->id);
    }

    public function test_document_version_relationships(): void
    {
        $user = $this->makeUser();
        $document = $this->makeDocument();
        $version = $this->makeVersion($document, $user);
        $chunk = $this->makeChunk($version);

        $this->assertSame($document->id, $version->document->id);
        $this->assertSame($user->id, $version->uploadedBy->id);
        $this->assertTrue($version->chunks()->exists());
        $this->assertSame($chunk->id, $version->chunks->first()->id);
    }

    public function test_document_chunk_belongs_to_version(): void
    {
        $user = $this->makeUser();
        $version = $this->makeVersion($this->makeDocument(), $user);
        $chunk = $this->makeChunk($version);

        $this->assertSame($version->id, $chunk->documentVersion->id);
    }

    public function test_llm_call_relationships(): void
    {
        $user = $this->makeUser();
        $run = $this->makeRun($user);
        $pricing = $this->makePricing();

        $call = LlmCall::create([
            'workflow_run_id' => $run->id,
            'user_id' => $user->id,
            'provider' => 'groq',
            'model' => 'llama-3.3',
            'input_price_per_1k' => 0.5,
            'output_price_per_1k' => 1.5,
            'model_pricing_id' => $pricing->id,
            'created_at' => now(),
        ]);

        $this->assertSame($run->id, $call->workflowRun->id);
        $this->assertSame($user->id, $call->user->id);
        $this->assertSame($pricing->id, $call->modelPricing->id);
    }

    public function test_model_pricing_has_many_llm_calls(): void
    {
        $user = $this->makeUser();
        $run = $this->makeRun($user);
        $pricing = $this->makePricing();

        LlmCall::create([
            'workflow_run_id' => $run->id,
            'user_id' => $user->id,
            'provider' => 'groq',
            'model' => 'llama-3.3',
            'input_price_per_1k' => 0.5,
            'output_price_per_1k' => 1.5,
            'model_pricing_id' => $pricing->id,
            'created_at' => now(),
        ]);

        $this->assertTrue($pricing->llmCalls()->exists());
    }

    public function test_document_version_casts(): void
    {
        $user = $this->makeUser();
        $version = $this->makeVersion($this->makeDocument(), $user);

        $casts = $version->getCasts();
        $this->assertSame('integer', $casts['chunk_count']);
        $this->assertSame('datetime', $casts['indexed_at']);
        $this->assertSame('datetime', $casts['created_at']);
        $this->assertInstanceOf(Carbon::class, $version->created_at);
    }

    public function test_document_chunk_casts(): void
    {
        $user = $this->makeUser();
        $chunk = $this->makeChunk($this->makeVersion($this->makeDocument(), $user));

        $casts = $chunk->getCasts();
        $this->assertSame('integer', $casts['chunk_index']);
        $this->assertSame('integer', $casts['page_number']);
        $this->assertSame('datetime', $casts['created_at']);
        $this->assertIsInt($chunk->chunk_index);
        $this->assertIsInt($chunk->page_number);
    }

    public function test_llm_call_casts(): void
    {
        $user = $this->makeUser();
        $run = $this->makeRun($user);
        $pricing = $this->makePricing();

        $call = LlmCall::create([
            'workflow_run_id' => $run->id,
            'user_id' => $user->id,
            'provider' => 'groq',
            'model' => 'llama-3.3',
            'input_tokens' => 100,
            'output_tokens' => 200,
            'input_price_per_1k' => 0.5,
            'output_price_per_1k' => 1.5,
            'model_pricing_id' => $pricing->id,
            'duration_ms' => 1234,
            'created_at' => now(),
        ]);

        $call->refresh();

        $casts = $call->getCasts();
        $this->assertSame('integer', $casts['duration_ms']);
        $this->assertSame('boolean', $casts['reconciled']);
        $this->assertSame('decimal:6', $casts['input_price_per_1k']);
        $this->assertIsInt($call->duration_ms);
        $this->assertIsBool($call->reconciled);
        $this->assertIsString($call->input_price_per_1k);
        $this->assertInstanceOf(Carbon::class, $call->created_at);
    }

    public function test_model_pricing_casts(): void
    {
        $pricing = $this->makePricing();

        $casts = $pricing->getCasts();
        $this->assertSame('decimal:6', $casts['input_price_per_1k']);
        $this->assertSame('date', $casts['effective_from']);
        $this->assertInstanceOf(Carbon::class, $pricing->effective_from);
        $this->assertInstanceOf(Carbon::class, $pricing->created_at);
    }

    public function test_document_version_guarded_fields_are_not_mass_assigned(): void
    {
        $user = $this->makeUser();

        $version = DocumentVersion::create([
            'document_id' => $this->makeDocument()->id,
            'version_label' => 'v1',
            'content_hash' => Str::uuid()->toString(),
            'file_path' => 'docs/law.pdf',
            'uploaded_by' => $user->id,
            'status' => 'indexed',
            'chunk_count' => 99,
            'created_at' => now(),
        ]);

        $version->refresh();

        $this->assertSame('pending', $version->status);
        $this->assertSame(0, $version->chunk_count);
        $this->assertNull($version->indexed_at);
    }

    public function test_llm_call_reconciled_is_guarded(): void
    {
        $user = $this->makeUser();
        $run = $this->makeRun($user);
        $pricing = $this->makePricing();

        $call = LlmCall::create([
            'workflow_run_id' => $run->id,
            'user_id' => $user->id,
            'provider' => 'groq',
            'model' => 'llama-3.3',
            'input_price_per_1k' => 0.5,
            'output_price_per_1k' => 1.5,
            'model_pricing_id' => $pricing->id,
            'reconciled' => true,
            'created_at' => now(),
        ]);

        $call->refresh();

        $this->assertFalse($call->reconciled);
    }

    public function test_deleting_document_version_cascades_to_chunks(): void
    {
        $user = $this->makeUser();
        $version = $this->makeVersion($this->makeDocument(), $user);
        $this->makeChunk($version);
        $this->makeChunk($version, 1);

        $version->delete();

        $this->assertSame(0, DocumentChunk::count());
    }

    public function test_deleting_document_with_versions_is_blocked_by_fk(): void
    {
        $user = $this->makeUser();
        $document = $this->makeDocument();
        $this->makeVersion($document, $user);

        $this->expectException(QueryException::class);

        try {
            $document->delete();
        } finally {
            $this->assertDatabaseHas('documents', ['id' => $document->id]);
        }
    }

    public function test_llm_call_with_invalid_model_pricing_is_blocked_by_fk(): void
    {
        $user = $this->makeUser();
        $run = $this->makeRun($user);

        $this->expectException(QueryException::class);

        LlmCall::create([
            'workflow_run_id' => $run->id,
            'user_id' => $user->id,
            'provider' => 'groq',
            'model' => 'llama-3.3',
            'input_price_per_1k' => 0.5,
            'output_price_per_1k' => 1.5,
            'model_pricing_id' => 999999,
            'created_at' => now(),
        ]);
    }

    public function test_fulltext_migration_runs_on_sqlite_without_error(): void
    {
        $this->assertTrue(Schema::hasTable('document_chunks'));

        if (DB::getDriverName() !== 'mysql') {
            $this->assertSame('sqlite', DB::getDriverName());

            return;
        }

        $indexes = DB::select("SHOW INDEX FROM document_chunks WHERE Index_type = 'FULLTEXT'");
        $this->assertNotEmpty($indexes);
    }
}

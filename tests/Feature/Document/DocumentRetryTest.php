<?php

namespace Tests\Feature\Document;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentRetryTest extends TestCase
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

    private function version(string $status, string $filename = 'law.pdf'): DocumentVersion
    {
        $user = $this->officer();
        $document = Document::create(['filename' => $filename]);

        return DocumentVersion::forceCreate([
            'document_id' => $document->id,
            'version_label' => 'v1',
            'content_hash' => hash('sha256', $filename.$status.\uniqid()),
            'file_path' => 'documents/'.$filename,
            'uploaded_by' => $user->id,
            'status' => $status,
            'failure_details' => $status === 'failed' ? 'boom' : null,
            'created_at' => now(),
        ]);
    }

    public function test_admin_sees_failed_documents_and_retry(): void
    {
        $version = $this->version('failed', 'broken.pdf');

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSee('Failed Documents')
            ->assertSee('broken.pdf')
            ->assertSee('Retry');
    }

    public function test_retry_dispatches_job_and_sets_pending(): void
    {
        Queue::fake();
        $version = $this->version('failed');

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->call('retryDocument', $version->id);

        Queue::assertPushed(ProcessDocumentJob::class, fn (ProcessDocumentJob $job) => $job->versionId === $version->id);
        $this->assertSame('pending', $version->fresh()->status);
    }

    public function test_officer_cannot_retry(): void
    {
        Queue::fake();
        $version = $this->version('failed');

        Livewire::actingAs($this->officer())
            ->test('copilot-chat')
            ->call('retryDocument', $version->id);

        Queue::assertNothingPushed();
        $this->assertSame('failed', $version->fresh()->status);
    }

    public function test_non_failed_version_cannot_be_retried(): void
    {
        Queue::fake();
        $version = $this->version('pending');

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->call('retryDocument', $version->id);

        Queue::assertNothingPushed();
        $this->assertSame('pending', $version->fresh()->status);
    }

    public function test_repeated_retry_enqueues_only_one_job(): void
    {
        Queue::fake();
        $version = $this->version('failed');

        $component = Livewire::actingAs($this->admin())->test('copilot-chat');
        $component->call('retryDocument', $version->id);
        $component->call('retryDocument', $version->id);

        Queue::assertPushed(ProcessDocumentJob::class, 1);
        $this->assertSame('pending', $version->fresh()->status);
    }

    public function test_failed_status_label_and_db_value_unchanged(): void
    {
        $version = $this->version('failed');

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSee('Failed');

        $this->assertSame('failed', $version->fresh()->status);
    }
}

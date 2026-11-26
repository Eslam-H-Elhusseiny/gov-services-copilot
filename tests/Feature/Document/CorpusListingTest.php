<?php

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CorpusListingTest extends TestCase
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

    private function makeDocument(string $status, string $filename, string $label = 'v1', int $chunks = 0): Document
    {
        $user = $this->officer();
        $document = Document::create(['filename' => $filename]);

        DocumentVersion::forceCreate([
            'document_id' => $document->id,
            'version_label' => $label,
            'content_hash' => hash('sha256', $filename.$label.\uniqid()),
            'file_path' => 'documents/'.$filename,
            'uploaded_by' => $user->id,
            'status' => $status,
            'chunk_count' => $chunks,
            'created_at' => now(),
        ]);

        return $document;
    }

    public function test_admin_sees_uploaded_document_with_status_label(): void
    {
        $this->makeDocument('indexed', 'civil-law.pdf', 'v2', 7);

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSee('civil-law.pdf')
            ->assertSee('v2')
            ->assertSee('Completed')
            ->assertSee('7 chunks');
    }

    public function test_empty_state_when_no_documents(): void
    {
        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSee('No documents uploaded yet');
    }

    public function test_status_labels_map_all_states(): void
    {
        $this->makeDocument('pending', 'a.pdf');
        $this->makeDocument('processing', 'b.pdf');
        $this->makeDocument('indexed', 'c.pdf');
        $this->makeDocument('failed', 'd.pdf');

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSee('Pending')
            ->assertSee('Processing')
            ->assertSee('Completed')
            ->assertSee('Failed');
    }

    public function test_upload_dispatches_document_uploaded_event(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4 test', 'application/pdf'))
            ->call('submitUpload')
            ->assertDispatched('document-uploaded');
    }

    public function test_officer_sees_documents_but_not_failed_section(): void
    {
        $this->makeDocument('indexed', 'officer-doc.pdf');

        Livewire::actingAs($this->officer())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSee('officer-doc.pdf')
            ->assertDontSee('Failed Documents');
    }
}

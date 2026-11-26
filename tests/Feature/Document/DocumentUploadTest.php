<?php

namespace Tests\Feature\Document;

use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Domain\Copilot\Document\TextChunk;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function pdf(string $name = 'doc.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::PDF, 'application/pdf');
    }

    private function docx(): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('gsc', true).'.docx';
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText('hello');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        $file = UploadedFile::fake()->createWithContent('doc.docx', file_get_contents($path), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        @unlink($path);

        return $file;
    }

    public function test_obsolete_standalone_upload_route_is_removed(): void
    {
        $this->actingAs($this->admin())->get('/documents/upload')->assertNotFound();
    }

    public function test_upload_button_is_enabled_when_file_selected(): void
    {
        $html = Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->pdf())
            ->html();

        $this->assertDoesNotMatchRegularExpression('/<button[^>]*\sdisabled[\s>]/', $html);
    }

    public function test_loading_is_tied_to_the_upload_action(): void
    {
        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->assertSee('wire:target="submitUpload"', escape: false)
            ->assertSee('wire:loading', escape: false);
    }

    public function test_admin_can_upload_valid_pdf(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->pdf())
            ->call('submitUpload')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('document_versions', ['status' => 'pending']);
        $this->assertCount(1, Storage::disk('local')->files('documents'));
    }

    public function test_admin_can_upload_valid_docx(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->docx())
            ->call('submitUpload')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('document_versions', ['status' => 'pending']);
    }

    public function test_invalid_extension_is_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', UploadedFile::fake()->createWithContent('doc.txt', 'plain', 'text/plain'))
            ->call('submitUpload')
            ->assertHasErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        $big = UploadedFile::fake()->createWithContent('big.pdf', str_repeat('a', 21 * 1024 * 1024), 'application/pdf');

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $big)
            ->call('submitUpload')
            ->assertHasErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_duplicate_content_hash_deletes_only_new_file(): void
    {
        Storage::fake('local');
        Queue::fake();
        $admin = $this->admin();

        Livewire::actingAs($admin)->test('document-upload')->set('file', $this->pdf())->call('submitUpload');
        Livewire::actingAs($admin)->test('document-upload')->set('file', $this->pdf())->call('submitUpload');

        $this->assertSame(1, DocumentVersion::count());
        $this->assertCount(1, Storage::disk('local')->files('documents'));
    }

    public function test_unexpected_failure_deletes_new_file(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->app->instance(DocumentRepository::class, new FailingUploadRepository);

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->pdf())
            ->call('submitUpload');

        $this->assertCount(0, Storage::disk('local')->files('documents'));
        Queue::assertNothingPushed();
    }

    public function test_successful_upload_dispatches_job_with_version_id(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->pdf())
            ->call('submitUpload');

        $version = DocumentVersion::first();

        Queue::assertPushed(ProcessDocumentJob::class, fn (ProcessDocumentJob $job) => $job->versionId === $version->id);
    }

    public function test_officer_cannot_invoke_upload_action(): void
    {
        Storage::fake('local');
        Queue::fake();

        try {
            Livewire::actingAs($this->officer())
                ->test('document-upload')
                ->set('file', $this->pdf())
                ->call('submitUpload');
        } catch (\Throwable) {
            // Authorization (or Livewire render) error — the action is blocked.
        }

        Queue::assertNothingPushed();
        $this->assertSame(0, DocumentVersion::count());
        $this->assertCount(0, Storage::disk('local')->files('documents'));
    }

    public function test_stored_path_is_private_and_uuid_based(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->pdf())
            ->call('submitUpload');

        $path = DocumentVersion::first()->file_path;

        $this->assertMatchesRegularExpression('#^documents/[0-9a-f-]{36}\.pdf$#', $path);
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_original_filename_preserved_safely(): void
    {
        Storage::fake('local');
        Queue::fake();

        Livewire::actingAs($this->admin())
            ->test('document-upload')
            ->set('file', $this->pdf('law.pdf'))
            ->call('submitUpload');

        $this->assertSame('law.pdf', Document::first()->filename);
    }
}

final class FailingUploadRepository implements DocumentRepository
{
    public function createDocumentVersion(int $userId, string $filename, string $contentHash, string $relativePath): array
    {
        throw new \RuntimeException('database failure');
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

    public function updateStatus(int $versionId, string $status, ?string $failure = null, ?int $chunkCount = null, bool $indexed = false): void {}
}

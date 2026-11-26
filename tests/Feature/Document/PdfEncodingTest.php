<?php

namespace Tests\Feature\Document;

use App\Domain\Copilot\Document\ParsedDocument;
use App\Domain\Copilot\Document\TextChunk;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use App\Infrastructure\Copilot\Document\EloquentDocumentRepository;
use App\Infrastructure\Copilot\Document\PdfDocumentParser;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Smalot\PdfParser\Document as PdfDocument;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class PdfEncodingTest extends TestCase
{
    use RefreshDatabase;

    private function parseWithText(string $text): ParsedDocument
    {
        $page = Mockery::mock(Page::class);
        $page->shouldReceive('getText')->andReturn($text);

        $pdf = Mockery::mock(PdfDocument::class);
        $pdf->shouldReceive('getPages')->andReturn([$page]);
        $pdf->shouldReceive('getText')->andReturn($text);

        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->andReturn($pdf);

        return (new PdfDocumentParser($parser))->parse('file.pdf');
    }

    public function test_valid_utf8_text_is_unchanged(): void
    {
        $text = 'Valid UTF-8: café — مرحبا';
        $result = $this->parseWithText($text);

        $this->assertSame($text, $result->text);
        $this->assertTrue(mb_check_encoding($result->text, 'UTF-8'));
    }

    public function test_windows_1252_bytes_are_converted(): void
    {
        $result = $this->parseWithText("Cost \x80\x94 5");

        $this->assertTrue(mb_check_encoding($result->text, 'UTF-8'));
        $this->assertStringContainsString('€', $result->text);
        $this->assertStringContainsString('”', $result->text);
    }

    public function test_arabic_text_remains_intact(): void
    {
        $text = 'المواطن يفقد بطاقة الهوية الوطنية';
        $result = $this->parseWithText($text);

        $this->assertSame($text, $result->text);
        $this->assertTrue(mb_check_encoding($result->text, 'UTF-8'));
    }

    public function test_mixed_text_is_always_valid_utf8(): void
    {
        $result = $this->parseWithText("Mixed \x80 text — café");

        $this->assertTrue(mb_check_encoding($result->text, 'UTF-8'));
        $this->assertTrue(mb_check_encoding($result->pages[1], 'UTF-8'));
    }

    public function test_normalized_chunk_persists_without_encoding_error(): void
    {
        $user = User::factory()->create(['role' => 'officer']);
        $document = Document::create(['filename' => 'law.pdf']);
        $version = DocumentVersion::forceCreate([
            'document_id' => $document->id,
            'version_label' => 'v1',
            'content_hash' => hash('sha256', 'law'),
            'file_path' => 'documents/law.pdf',
            'uploaded_by' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $normalized = mb_convert_encoding("bad \x80\x94 text", 'UTF-8', 'Windows-1252');
        $repo = new EloquentDocumentRepository(new EncodingNullVectorIndex);

        $id = $repo->saveChunk($version->id, new TextChunk(0, $normalized, null, null, null));

        $this->assertDatabaseHas('document_chunks', ['id' => $id, 'document_version_id' => $version->id]);
    }
}

final class EncodingNullVectorIndex implements VectorIndex
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

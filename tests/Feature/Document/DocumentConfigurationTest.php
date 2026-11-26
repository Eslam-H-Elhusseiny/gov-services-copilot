<?php

namespace Tests\Feature\Document;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Document\IngestDocument;
use App\Application\Copilot\Llm\LlmProviderManager;
use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Infrastructure\Copilot\Document\DocxDocumentParser;
use App\Infrastructure\Copilot\Document\EloquentDocumentRepository;
use App\Infrastructure\Copilot\Document\PdfDocumentParser;
use App\Infrastructure\Copilot\Document\RoutingDocumentParser;
use App\Infrastructure\Copilot\EloquentCaseRepository;
use App\Infrastructure\Copilot\Embedding\GeminiEmbeddingProvider;
use App\Infrastructure\Copilot\Vector\QdrantVectorIndex;
use Tests\TestCase;

class DocumentConfigurationTest extends TestCase
{
    private function prop(object $object, string $name): mixed
    {
        $property = new \ReflectionProperty($object, $name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function test_document_repository_resolves_to_eloquent(): void
    {
        $this->assertInstanceOf(EloquentDocumentRepository::class, app(DocumentRepository::class));
    }

    public function test_document_parser_resolves_to_routing(): void
    {
        $this->assertInstanceOf(RoutingDocumentParser::class, app(DocumentParser::class));
    }

    public function test_routing_parser_receives_pdf_and_docx_dependencies(): void
    {
        $parser = app(DocumentParser::class);

        $this->assertInstanceOf(PdfDocumentParser::class, $this->prop($parser, 'pdf'));
        $this->assertInstanceOf(DocxDocumentParser::class, $this->prop($parser, 'docx'));
    }

    public function test_ingest_document_resolves(): void
    {
        $this->assertInstanceOf(IngestDocument::class, app(IngestDocument::class));
    }

    public function test_ingest_document_receives_configured_chunk_size_and_overlap(): void
    {
        $ingest = app(IngestDocument::class);

        $this->assertSame(config('documents.chunk_size'), $this->prop($ingest, 'chunkSize'));
        $this->assertSame(config('documents.chunk_overlap'), $this->prop($ingest, 'chunkOverlap'));
    }

    public function test_configuration_defaults(): void
    {
        $this->assertSame('local', config('documents.disk'));
        $this->assertSame(20480, config('documents.max_upload_kb'));
        $this->assertSame(['pdf', 'docx'], config('documents.allowed_extensions'));
        $this->assertSame(1000, config('documents.chunk_size'));
        $this->assertSame(200, config('documents.chunk_overlap'));
    }

    public function test_environment_overrides_are_applied(): void
    {
        putenv('DOCUMENT_CHUNK_SIZE=555');
        $_ENV['DOCUMENT_CHUNK_SIZE'] = '555';
        putenv('DOCUMENT_ALLOWED_EXTENSIONS=PDF, Docx');
        $_ENV['DOCUMENT_ALLOWED_EXTENSIONS'] = 'PDF, Docx';

        $config = require base_path('config/documents.php');

        $this->assertSame(555, $config['chunk_size']);
        $this->assertSame(['pdf', 'docx'], $config['allowed_extensions']);

        putenv('DOCUMENT_CHUNK_SIZE');
        unset($_ENV['DOCUMENT_CHUNK_SIZE']);
        putenv('DOCUMENT_ALLOWED_EXTENSIONS');
        unset($_ENV['DOCUMENT_ALLOWED_EXTENSIONS']);
    }

    public function test_existing_bindings_remain_functional(): void
    {
        $this->assertInstanceOf(EloquentCaseRepository::class, app(CaseRepository::class));
        $this->assertInstanceOf(GeminiEmbeddingProvider::class, app(EmbeddingProvider::class));
        $this->assertInstanceOf(QdrantVectorIndex::class, app(VectorIndex::class));
        $this->assertInstanceOf(LlmProviderManager::class, app(LlmProviderManager::class));
    }
}

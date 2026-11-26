<?php

namespace Tests\Feature\Document;

use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\ParsedDocument;
use App\Infrastructure\Copilot\Document\DocxDocumentParser;
use App\Infrastructure\Copilot\Document\PdfDocumentParser;
use App\Infrastructure\Copilot\Document\RoutingDocumentParser;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mockery;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class DocumentParsersTest extends TestCase
{
    public function test_pdf_extraction_with_one_based_pages(): void
    {
        $page1 = Mockery::mock(Page::class);
        $page1->shouldReceive('getText')->andReturn('Page one');
        $page2 = Mockery::mock(Page::class);
        $page2->shouldReceive('getText')->andReturn('Page two');

        $pdf = Mockery::mock(Document::class);
        $pdf->shouldReceive('getPages')->andReturn([$page1, $page2]);
        $pdf->shouldReceive('getText')->andReturn('Page one Page two');

        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->andReturn($pdf);

        $result = (new PdfDocumentParser($parser))->parse('file.pdf');

        $this->assertInstanceOf(ParsedDocument::class, $result);
        $this->assertSame([1 => 'Page one', 2 => 'Page two'], $result->pages);
        $this->assertSame('Page one Page two', $result->text);
    }

    public function test_pdf_falls_back_to_text_when_no_pages(): void
    {
        $pdf = Mockery::mock(Document::class);
        $pdf->shouldReceive('getPages')->andReturn([]);
        $pdf->shouldReceive('getText')->andReturn('full text only');

        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->andReturn($pdf);

        $result = (new PdfDocumentParser($parser))->parse('file.pdf');

        $this->assertSame([], $result->pages);
        $this->assertSame('full text only', $result->text);
    }

    public function test_pdf_invalid_file_throws(): void
    {
        $parser = Mockery::mock(Parser::class);
        $parser->shouldReceive('parseFile')->andThrow(new \Exception('bad pdf'));

        $this->expectException(RuntimeException::class);
        (new PdfDocumentParser($parser))->parse('bad.pdf');
    }

    public function test_docx_extraction_from_paragraphs_and_tables(): void
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('gsc', true).'.docx';

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Hello paragraph');
        $table = $section->addTable();
        $table->addRow();
        $table->addCell()->addText('Cell A');
        $table->addCell()->addText('Cell B');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $result = (new DocxDocumentParser)->parse($path);

        $this->assertSame([], $result->pages);
        $this->assertStringContainsString('Hello paragraph', $result->text);
        $this->assertStringContainsString('Cell A', $result->text);
        $this->assertStringContainsString('Cell B', $result->text);

        @unlink($path);
    }

    public function test_docx_invalid_file_throws(): void
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('gsc', true).'.docx';
        file_put_contents($path, 'not a real docx');

        $this->expectException(RuntimeException::class);
        (new DocxDocumentParser)->parse($path);

        @unlink($path);
    }

    public function test_routing_selects_parser_by_extension(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('doc.pdf', 'x');
        Storage::disk('local')->put('doc.docx', 'x');

        $pdf = new class implements DocumentParser
        {
            public string $received = '';

            public function parse(string $relativePath): ParsedDocument
            {
                $this->received = $relativePath;

                return new ParsedDocument('pdf-text', []);
            }
        };
        $docx = new class implements DocumentParser
        {
            public string $received = '';

            public function parse(string $relativePath): ParsedDocument
            {
                $this->received = $relativePath;

                return new ParsedDocument('docx-text', []);
            }
        };

        $router = new RoutingDocumentParser($pdf, $docx);

        $this->assertSame('pdf-text', $router->parse('doc.pdf')->text);
        $this->assertSame(Storage::disk('local')->path('doc.pdf'), $pdf->received);

        $this->assertSame('docx-text', $router->parse('doc.docx')->text);
        $this->assertSame(Storage::disk('local')->path('doc.docx'), $docx->received);
    }

    public function test_routing_rejects_unsupported_extension(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('file.txt', 'x');

        $router = new RoutingDocumentParser(
            new class implements DocumentParser
            {
                public function parse(string $relativePath): ParsedDocument
                {
                    return new ParsedDocument('', []);
                }
            },
            new class implements DocumentParser
            {
                public function parse(string $relativePath): ParsedDocument
                {
                    return new ParsedDocument('', []);
                }
            },
        );

        $this->expectException(InvalidArgumentException::class);
        $router->parse('file.txt');
    }

    public function test_routing_rejects_missing_file(): void
    {
        Storage::fake('local');

        $router = new RoutingDocumentParser(
            new class implements DocumentParser
            {
                public function parse(string $relativePath): ParsedDocument
                {
                    return new ParsedDocument('', []);
                }
            },
            new class implements DocumentParser
            {
                public function parse(string $relativePath): ParsedDocument
                {
                    return new ParsedDocument('', []);
                }
            },
        );

        $this->expectException(InvalidArgumentException::class);
        $router->parse('missing.pdf');
    }
}

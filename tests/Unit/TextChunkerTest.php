<?php

namespace Tests\Unit;

use App\Domain\Copilot\Document\ParsedDocument;
use App\Domain\Copilot\Document\TextChunker;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TextChunkerTest extends TestCase
{
    public function test_single_page_chunking(): void
    {
        $document = new ParsedDocument(str_repeat('a', 120), [1 => str_repeat('a', 120)]);

        $chunks = (new TextChunker)->chunk($document, 50, 10);

        $this->assertNotEmpty($chunks);
        $this->assertSame(1, $chunks[0]->pageNumber);
        $this->assertSame(0, $chunks[0]->index);
    }

    public function test_multi_page_chunking_does_not_cross_pages(): void
    {
        $document = new ParsedDocument('', [
            1 => str_repeat('a', 120),
            2 => str_repeat('b', 120),
        ]);

        $chunks = (new TextChunker)->chunk($document, 50, 10);

        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            if ($chunk->pageNumber === 1) {
                $this->assertStringNotContainsString('b', $chunk->content);
            } else {
                $this->assertSame(2, $chunk->pageNumber);
                $this->assertStringNotContainsString('a', $chunk->content);
            }
        }

        $this->assertSame([1, 2], array_values(array_unique(array_map(fn ($c) => $c->pageNumber, $chunks))));
    }

    public function test_docx_style_text_chunking_uses_null_page(): void
    {
        $document = new ParsedDocument(str_repeat('word ', 60), []);

        $chunks = (new TextChunker)->chunk($document, 60, 10);

        $this->assertNotEmpty($chunks);
        $this->assertNull($chunks[0]->pageNumber);
    }

    public function test_empty_document_returns_no_chunks(): void
    {
        $chunks = (new TextChunker)->chunk(new ParsedDocument('', []), 100, 10);

        $this->assertSame([], $chunks);
    }

    public function test_pdf_falls_back_to_text_when_pages_empty(): void
    {
        $chunks = (new TextChunker)->chunk(new ParsedDocument('hello world', []), 100, 10);

        $this->assertCount(1, $chunks);
        $this->assertNull($chunks[0]->pageNumber);
        $this->assertSame('hello world', $chunks[0]->content);
    }

    public function test_overlap_is_applied(): void
    {
        $document = new ParsedDocument(str_repeat('a', 200), []);

        $chunks = (new TextChunker)->chunk($document, 50, 10);

        $this->assertGreaterThanOrEqual(2, count($chunks));
        $this->assertSame(substr($chunks[0]->content, -10), substr($chunks[1]->content, 0, 10));
    }

    public function test_indexes_are_sequential_globally(): void
    {
        $document = new ParsedDocument('', [
            1 => str_repeat('a', 120),
            2 => str_repeat('b', 120),
        ]);

        $chunks = (new TextChunker)->chunk($document, 50, 10);

        $indexes = array_map(fn ($c) => $c->index, $chunks);
        $this->assertSame(range(0, count($chunks) - 1), $indexes);
    }

    public function test_invalid_size_and_overlap_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TextChunker)->chunk(new ParsedDocument('x', []), 0, 0);
    }

    public function test_negative_overlap_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TextChunker)->chunk(new ParsedDocument('x', []), 50, -1);
    }

    public function test_overlap_larger_than_size_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TextChunker)->chunk(new ParsedDocument('x', []), 50, 50);
    }

    public function test_section_and_clause_are_null(): void
    {
        $chunks = (new TextChunker)->chunk(new ParsedDocument(str_repeat('a', 120), [1 => str_repeat('a', 120)]), 50, 10);

        foreach ($chunks as $chunk) {
            $this->assertNull($chunk->section);
            $this->assertNull($chunk->clause);
        }
    }
}

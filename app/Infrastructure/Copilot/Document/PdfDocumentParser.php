<?php

namespace App\Infrastructure\Copilot\Document;

use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\ParsedDocument;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

final class PdfDocumentParser implements DocumentParser
{
    public function __construct(private Parser $parser) {}

    public function parse(string $relativePath): ParsedDocument
    {
        try {
            $pdf = $this->parser->parseFile($relativePath);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to read the PDF document.', 0, $e);
        }

        $pages = [];

        foreach ($pdf->getPages() as $index => $page) {
            $pageText = (string) $page->getText();

            if (trim($pageText) !== '') {
                $pages[$index + 1] = $pageText;
            }
        }

        return new ParsedDocument((string) $pdf->getText(), $pages);
    }
}

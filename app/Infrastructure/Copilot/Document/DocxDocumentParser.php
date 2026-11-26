<?php

namespace App\Infrastructure\Copilot\Document;

use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\ParsedDocument;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Throwable;

final class DocxDocumentParser implements DocumentParser
{
    public function parse(string $relativePath): ParsedDocument
    {
        try {
            $phpWord = IOFactory::load($relativePath);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to read the DOCX document.', 0, $e);
        }

        $lines = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text = $this->elementText($element);

                if (trim($text) !== '') {
                    $lines[] = $text;
                }
            }
        }

        return new ParsedDocument(implode("\n", $lines), []);
    }

    private function elementText(mixed $element): string
    {
        if ($element instanceof Text) {
            return (string) $element->getText();
        }

        if ($element instanceof TextRun) {
            $parts = [];

            foreach ($element->getElements() as $child) {
                $parts[] = $this->elementText($child);
            }

            return implode('', $parts);
        }

        if ($element instanceof Table) {
            $rows = [];

            foreach ($element->getRows() as $row) {
                $cells = [];

                foreach ($row->getCells() as $cell) {
                    $cells[] = $this->elementText($cell);
                }

                $rows[] = implode(' | ', $cells);
            }

            return implode("\n", $rows);
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];

            foreach ($element->getElements() as $child) {
                $parts[] = $this->elementText($child);
            }

            return implode('', $parts);
        }

        return method_exists($element, 'getText') ? (string) $element->getText() : '';
    }
}

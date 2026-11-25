<?php

namespace App\Domain\Copilot\Document;

use InvalidArgumentException;

final class TextChunker
{
    /**
     * @return array<int, TextChunk>
     */
    public function chunk(ParsedDocument $document, int $size, int $overlap): array
    {
        if ($size <= 0) {
            throw new InvalidArgumentException('Chunk size must be positive.');
        }

        if ($overlap < 0) {
            throw new InvalidArgumentException('Chunk overlap must be non-negative.');
        }

        if ($overlap >= $size) {
            throw new InvalidArgumentException('Chunk overlap must be smaller than the chunk size.');
        }

        $chunks = [];
        $index = 0;

        if ($document->pages !== []) {
            $pages = $document->pages;
            ksort($pages);

            foreach ($pages as $pageNumber => $pageText) {
                foreach ($this->split((string) $pageText, $size, $overlap, (int) $pageNumber, $index) as $chunk) {
                    $chunks[] = $chunk;
                }
            }

            return $chunks;
        }

        foreach ($this->split($document->text, $size, $overlap, null, $index) as $chunk) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * @return array<int, TextChunk>
     */
    private function split(string $text, int $size, int $overlap, ?int $pageNumber, int &$index): array
    {
        $chunks = [];
        $length = strlen($text);

        if ($length === 0) {
            return $chunks;
        }

        $position = 0;

        while ($position < $length) {
            $remaining = $length - $position;

            if ($remaining <= $size) {
                $piece = substr($text, $position);
                $nextPosition = $length;
            } else {
                $window = substr($text, $position, $size);
                $lastSpace = strrpos($window, ' ');

                if ($lastSpace !== false && $lastSpace >= (int) floor($size / 2)) {
                    $piece = substr($window, 0, $lastSpace);
                    $nextPosition = $position + $lastSpace;
                } else {
                    $piece = $window;
                    $nextPosition = $position + $size;
                }
            }

            $piece = trim($piece);

            if ($piece !== '') {
                $chunks[] = new TextChunk($index++, $piece, $pageNumber, null, null);
            }

            if ($nextPosition >= $length) {
                break;
            }

            $step = $nextPosition - $position;
            $position += max(1, $step - $overlap);
        }

        return $chunks;
    }
}

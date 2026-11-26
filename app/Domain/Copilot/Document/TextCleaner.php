<?php

namespace App\Domain\Copilot\Document;

final class TextCleaner
{
    public function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = $this->toValidUtf8($text);

        return trim($text);
    }

    public function cleanParsed(ParsedDocument $document): ParsedDocument
    {
        $pages = [];

        foreach ($document->pages as $pageNumber => $pageText) {
            $pages[$pageNumber] = $this->clean((string) $pageText);
        }

        return new ParsedDocument($this->clean($document->text), $pages);
    }

    private function toValidUtf8(string $text): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $result = '';
        $length = strlen($text);

        for ($i = 0; $i < $length;) {
            $byte = ord($text[$i]);

            $sequenceLength = match (true) {
                $byte >= 0xF0 => 4,
                $byte >= 0xE0 => 3,
                $byte >= 0xC0 => 2,
                default => 1,
            };

            if ($sequenceLength > 1 && $i + $sequenceLength <= $length) {
                $candidate = substr($text, $i, $sequenceLength);

                if (mb_check_encoding($candidate, 'UTF-8')) {
                    $result .= $candidate;
                    $i += $sequenceLength;

                    continue;
                }
            }

            // ASCII passes through; isolated invalid bytes are Windows-1252.
            $result .= $byte < 0x80
                ? $text[$i]
                : mb_convert_encoding(chr($byte), 'UTF-8', 'Windows-1252');

            $i++;
        }

        return $result;
    }
}

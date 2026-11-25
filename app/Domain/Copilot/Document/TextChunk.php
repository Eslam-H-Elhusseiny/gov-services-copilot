<?php

namespace App\Domain\Copilot\Document;

final readonly class TextChunk
{
    public function __construct(
        public int $index,
        public string $content,
        public ?int $pageNumber,
        public ?string $section,
        public ?string $clause,
    ) {}
}

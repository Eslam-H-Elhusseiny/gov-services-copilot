<?php

namespace App\Domain\Copilot\Document;

final readonly class ParsedDocument
{
    /**
     * @param  array<int, string>  $pages  Page number => extracted page text
     */
    public function __construct(
        public string $text,
        public array $pages,
    ) {}
}

<?php

namespace App\Domain\Copilot\Retrieval;

final readonly class RetrievedChunk
{
    public function __construct(
        public int $id,
        public string $content,
        public int $documentId,
        public int $documentVersionId,
        public string $filename,
        public ?int $pageNumber,
        public ?string $section,
        public ?string $clause,
        public float $rrfScore,
        public int $rank,
        public string $sources,
    ) {}
}

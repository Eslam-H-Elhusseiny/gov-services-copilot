<?php

namespace App\Domain\Copilot\Retrieval;

final readonly class RetrievalOutcome
{
    /**
     * @param  array<int, RetrievedChunk>  $chunks
     */
    public function __construct(
        public array $chunks,
        public bool $sufficientEvidence,
        public bool $potentialConflicts,
    ) {}
}

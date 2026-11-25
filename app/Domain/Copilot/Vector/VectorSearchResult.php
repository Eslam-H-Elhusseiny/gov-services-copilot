<?php

namespace App\Domain\Copilot\Vector;

final readonly class VectorSearchResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $id,
        public float $score,
        public array $payload,
    ) {}
}

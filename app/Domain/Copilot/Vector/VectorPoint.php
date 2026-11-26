<?php

namespace App\Domain\Copilot\Vector;

final readonly class VectorPoint
{
    /**
     * @param  array<int, float>  $vector
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $id,
        public array $vector,
        public array $payload,
    ) {}
}

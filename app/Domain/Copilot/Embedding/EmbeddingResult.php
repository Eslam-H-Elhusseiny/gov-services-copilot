<?php

namespace App\Domain\Copilot\Embedding;

final readonly class EmbeddingResult
{
    /**
     * @param  array<int, float>  $vector
     */
    public function __construct(
        public string $provider,
        public string $model,
        public int $dimensions,
        public array $vector,
        public ?int $inputTokens,
    ) {}
}

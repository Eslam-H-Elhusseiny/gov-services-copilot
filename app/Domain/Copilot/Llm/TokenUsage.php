<?php

namespace App\Domain\Copilot\Llm;

final readonly class TokenUsage
{
    public function __construct(
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
    ) {}
}

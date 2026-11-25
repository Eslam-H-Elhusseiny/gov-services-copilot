<?php

namespace App\Domain\Copilot\Llm;

final readonly class ToolCall
{
    public function __construct(
        public string $id,
        public string $name,
        public string $argumentsJson,
    ) {}
}

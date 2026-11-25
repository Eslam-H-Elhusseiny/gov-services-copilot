<?php

namespace App\Domain\Copilot\Llm;

final readonly class LlmTool
{
    /**
     * @param  array<string, mixed>  $parametersJsonSchema
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $parametersJsonSchema,
    ) {}
}

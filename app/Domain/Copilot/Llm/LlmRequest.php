<?php

namespace App\Domain\Copilot\Llm;

final readonly class LlmRequest
{
    /**
     * @param  array<int, LlmMessage>  $messages
     * @param  array<int, LlmTool>  $tools
     * @param  array<string, mixed>|null  $responseSchema
     */
    public function __construct(
        public string $model,
        public array $messages,
        public array $tools = [],
        public ?array $responseSchema = null,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
    ) {}
}

<?php

namespace App\Domain\Copilot\Llm;

final readonly class LlmResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     */
    public function __construct(
        public ?string $text,
        public array $toolCalls,
        public ?string $finishReason,
        public ?TokenUsage $usage,
        public string $provider,
        public string $model,
        public int $durationMs,
    ) {}
}

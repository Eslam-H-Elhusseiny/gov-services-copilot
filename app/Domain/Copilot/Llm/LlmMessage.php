<?php

namespace App\Domain\Copilot\Llm;

final readonly class LlmMessage
{
    /**
     * @param  array<int, ToolCall>|null  $toolCalls
     */
    public function __construct(
        public string $role,
        public ?string $content = null,
        public ?array $toolCalls = null,
        public ?string $toolCallId = null,
    ) {}
}

<?php

namespace App\Domain\Copilot\Llm;

enum LlmCapability: string
{
    case Text = 'text';
    case ToolCalling = 'tool_calling';
    case StructuredOutput = 'structured_output';
    case Streaming = 'streaming';
}

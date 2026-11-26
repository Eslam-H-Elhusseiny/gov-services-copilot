<?php

namespace App\Infrastructure\Copilot\Llm;

use App\Domain\Copilot\Llm\LlmCapability;
use App\Domain\Copilot\Llm\LlmProvider;
use App\Domain\Copilot\Llm\LlmProviderException;
use App\Domain\Copilot\Llm\LlmRequest;
use App\Domain\Copilot\Llm\LlmResponse;
use App\Domain\Copilot\Llm\TokenUsage;
use App\Domain\Copilot\Llm\ToolCall;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class GroqProvider implements LlmProvider
{
    private const STRUCTURED_OUTPUT_MODELS = [
        'openai/gpt-oss-20b',
        'openai/gpt-oss-120b',
        'qwen/qwen3.8-27b',
        'meta-models/muse-spark-open',
    ];

    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'groq';
    }

    public function supports(LlmCapability $capability, ?string $model = null): bool
    {
        $model ??= $this->config['model'];

        return match ($capability) {
            LlmCapability::Text => true,
            LlmCapability::ToolCalling => true,
            LlmCapability::StructuredOutput => in_array($model, self::STRUCTURED_OUTPUT_MODELS, true),
            LlmCapability::Streaming => false,
        };
    }

    public function supportsRequest(LlmRequest $request): bool
    {
        $hasTools = $request->tools !== [];
        $hasSchema = $request->responseSchema !== null;

        // Groq does not support tool use together with structured outputs.
        if ($hasTools && $hasSchema) {
            return false;
        }

        if ($hasSchema && ! $this->supports(LlmCapability::StructuredOutput, $request->model)) {
            return false;
        }

        if ($hasTools && ! $this->supports(LlmCapability::ToolCalling, $request->model)) {
            return false;
        }

        return true;
    }

    public function generate(LlmRequest $request): LlmResponse
    {
        if (! $this->supportsRequest($request)) {
            throw new LlmProviderException($this->name(), 'Unsupported request for Groq.', null, false);
        }

        $body = [
            'model' => $request->model,
            'messages' => $this->mapMessages($request),
        ];

        if ($request->tools !== []) {
            $body['tools'] = array_map(static fn ($tool) => [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'parameters' => $tool->parametersJsonSchema,
                ],
            ], $request->tools);
        }

        if ($request->responseSchema !== null) {
            $body['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'response',
                    'strict' => true,
                    'schema' => $request->responseSchema,
                ],
            ];
        }

        if ($request->temperature !== null) {
            $body['temperature'] = $request->temperature;
        }

        if ($request->maxTokens !== null) {
            $body['max_completion_tokens'] = $request->maxTokens;
        }

        $start = microtime(true);

        try {
            $response = Http::withToken((string) $this->config['key'])
                ->timeout((int) $this->config['timeout'])
                ->post($this->config['base_uri'].'/chat/completions', $body);
        } catch (ConnectionException) {
            throw new LlmProviderException($this->name(), 'Connection to Groq failed.', null, true);
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if ($response->status() >= 400) {
            throw $this->error($response->status(), $response->body());
        }

        $json = $response->json();
        $message = $json['choices'][0]['message'] ?? [];
        $text = $message['content'] ?? null;

        $toolCalls = [];
        foreach ($message['tool_calls'] ?? [] as $toolCall) {
            $toolCalls[] = new ToolCall(
                (string) ($toolCall['id'] ?? ''),
                (string) ($toolCall['function']['name'] ?? ''),
                (string) ($toolCall['function']['arguments'] ?? ''),
            );
        }

        $usage = isset($json['usage'])
            ? new TokenUsage($json['usage']['input_tokens'] ?? null, $json['usage']['output_tokens'] ?? null)
            : null;

        return new LlmResponse(
            $text,
            $toolCalls,
            $json['choices'][0]['finish_reason'] ?? null,
            $usage,
            $this->name(),
            $request->model,
            $durationMs,
        );
    }

    private function mapMessages(LlmRequest $request): array
    {
        $messages = [];

        foreach ($request->messages as $message) {
            if ($message->role === 'tool') {
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $message->toolCallId,
                    'content' => $message->content,
                ];

                continue;
            }

            if ($message->role === 'assistant' && $message->toolCalls !== null) {
                $toolCalls = [];
                foreach ($message->toolCalls as $toolCall) {
                    $toolCalls[] = [
                        'id' => $toolCall->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $toolCall->name,
                            'arguments' => $toolCall->argumentsJson,
                        ],
                    ];
                }

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $message->content,
                    'tool_calls' => $toolCalls,
                ];

                continue;
            }

            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        return $messages;
    }

    private function error(int $status, string $body): LlmProviderException
    {
        $retryable = in_array($status, [408, 429], true) || $status >= 500;

        return new LlmProviderException(
            $this->name(),
            sprintf('Groq API error (HTTP %d).', $status),
            $status,
            $retryable,
            $this->sanitize($body),
        );
    }

    private function sanitize(string $body): string
    {
        $body = str_replace((string) $this->config['key'], '[REDACTED]', $body);

        return substr($body, 0, 500);
    }
}

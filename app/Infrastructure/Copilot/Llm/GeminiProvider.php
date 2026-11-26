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

final class GeminiProvider implements LlmProvider
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function supports(LlmCapability $capability, ?string $model = null): bool
    {
        return match ($capability) {
            LlmCapability::Text => true,
            LlmCapability::ToolCalling => true,
            LlmCapability::StructuredOutput => true,
            LlmCapability::Streaming => false,
        };
    }

    public function supportsRequest(LlmRequest $request): bool
    {
        // MVP application-level restriction: tool calling + structured output is
        // not combined for Gemini in this milestone (not an official API limit).
        if ($request->tools !== [] && $request->responseSchema !== null) {
            return false;
        }

        return true;
    }

    public function generate(LlmRequest $request): LlmResponse
    {
        if (! $this->supportsRequest($request)) {
            throw new LlmProviderException($this->name(), 'Unsupported request for Gemini.', null, false);
        }

        $body = ['contents' => $this->mapContents($request)];

        $systemInstruction = $this->mapSystemInstruction($request);
        if ($systemInstruction !== null) {
            $body['systemInstruction'] = $systemInstruction;
        }

        if ($request->tools !== []) {
            $body['tools'] = [
                [
                    'functionDeclarations' => array_map(static fn ($tool) => [
                        'name' => $tool->name,
                        'description' => $tool->description,
                        'parameters' => $tool->parametersJsonSchema,
                    ], $request->tools),
                ],
            ];
        }

        $generationConfig = [];
        if ($request->responseSchema !== null) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $request->responseSchema;
        }
        if ($request->temperature !== null) {
            $generationConfig['temperature'] = $request->temperature;
        }
        if ($request->maxTokens !== null) {
            $generationConfig['maxOutputTokens'] = $request->maxTokens;
        }
        if ($generationConfig !== []) {
            $body['generationConfig'] = $generationConfig;
        }

        $url = $this->config['base_uri'].'/models/'.$request->model.':generateContent?key='.(string) $this->config['key'];

        $start = microtime(true);

        try {
            $response = Http::timeout((int) $this->config['timeout'])->post($url, $body);
        } catch (ConnectionException) {
            throw new LlmProviderException($this->name(), 'Connection to Gemini failed.', null, true);
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if ($response->status() >= 400) {
            throw $this->error($response->status(), $response->body());
        }

        $json = $response->json();
        $parts = $json['candidates'][0]['content']['parts'] ?? [];

        $text = '';
        $toolCalls = [];
        foreach ($parts as $index => $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $functionCall = $part['functionCall'];
                $toolCalls[] = new ToolCall(
                    (string) ($functionCall['id'] ?? 'call_'.$index),
                    (string) ($functionCall['name'] ?? ''),
                    (string) json_encode($functionCall['args'] ?? []),
                );
            }
        }

        $usage = isset($json['usageMetadata'])
            ? new TokenUsage($json['usageMetadata']['promptTokenCount'] ?? null, $json['usageMetadata']['candidatesTokenCount'] ?? null)
            : null;

        return new LlmResponse(
            $text === '' ? null : $text,
            $toolCalls,
            $json['candidates'][0]['finishReason'] ?? null,
            $usage,
            $this->name(),
            $request->model,
            $durationMs,
        );
    }

    private function mapContents(LlmRequest $request): array
    {
        $contents = [];

        foreach ($request->messages as $message) {
            if ($message->role === 'system') {
                continue;
            }

            if ($message->role === 'tool') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $message->toolCallId,
                                'response' => (array) json_decode((string) $message->content, true),
                            ],
                        ],
                    ],
                ];

                continue;
            }

            $role = $message->role === 'assistant' ? 'model' : 'user';
            $parts = [];

            if ($message->toolCalls !== null) {
                foreach ($message->toolCalls as $toolCall) {
                    $parts[] = [
                        'functionCall' => [
                            'name' => $toolCall->name,
                            'args' => (array) json_decode($toolCall->argumentsJson, true),
                        ],
                    ];
                }
            } elseif ($message->content !== null) {
                $parts[] = ['text' => $message->content];
            }

            $contents[] = [
                'role' => $role,
                'parts' => $parts,
            ];
        }

        return $contents;
    }

    private function mapSystemInstruction(LlmRequest $request): ?array
    {
        foreach ($request->messages as $message) {
            if ($message->role === 'system' && $message->content !== null) {
                return ['parts' => [['text' => $message->content]]];
            }
        }

        return null;
    }

    private function error(int $status, string $body): LlmProviderException
    {
        $retryable = in_array($status, [408, 429], true) || $status >= 500;

        return new LlmProviderException(
            $this->name(),
            sprintf('Gemini API error (HTTP %d).', $status),
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

<?php

namespace App\Infrastructure\Copilot\Embedding;

use App\Domain\Copilot\Embedding\EmbeddingException;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Embedding\EmbeddingResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class GeminiEmbeddingProvider implements EmbeddingProvider
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function dimensions(): int
    {
        return (int) $this->config['dimensions'];
    }

    public function embed(string $text): EmbeddingResult
    {
        if (trim($text) === '') {
            throw new EmbeddingException($this->name(), 'Cannot embed empty text.');
        }

        $key = (string) ($this->config['key'] ?? '');

        if ($key === '') {
            throw new EmbeddingException($this->name(), 'Missing embedding API key.');
        }

        $body = [
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
            'embedContentConfig' => [
                'taskType' => $this->config['task_type'] ?? 'RETRIEVAL_DOCUMENT',
                'outputDimensionality' => $this->dimensions(),
            ],
        ];

        $url = $this->config['base_uri'].'/models/'.$this->config['model'].':embedContent';

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout((int) $this->config['timeout'])
                ->post($url, $body);
        } catch (ConnectionException) {
            throw new EmbeddingException($this->name(), 'Connection to Gemini embeddings failed.', null, true);
        }

        if ($response->status() >= 400) {
            throw $this->error($response->status(), $response->body());
        }

        $json = $response->json();
        $values = $json['embedding']['values'] ?? null;

        if (! is_array($values)) {
            throw new EmbeddingException($this->name(), 'Invalid embedding response.');
        }

        if (count($values) !== $this->dimensions()) {
            throw new EmbeddingException($this->name(), 'Embedding dimension mismatch.');
        }

        return new EmbeddingResult(
            $this->name(),
            (string) $this->config['model'],
            $this->dimensions(),
            array_values($values),
            null,
        );
    }

    private function error(int $status, string $body): EmbeddingException
    {
        $retryable = in_array($status, [408, 429], true) || $status >= 500;

        return new EmbeddingException(
            $this->name(),
            sprintf('Gemini embeddings error (HTTP %d).', $status),
            $status,
            $retryable,
            $this->sanitize($body),
        );
    }

    private function sanitize(string $body): string
    {
        $body = str_replace((string) ($this->config['key'] ?? ''), '[REDACTED]', $body);

        return substr($body, 0, 500);
    }
}

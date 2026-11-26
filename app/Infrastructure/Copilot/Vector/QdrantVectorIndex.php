<?php

namespace App\Infrastructure\Copilot\Vector;

use App\Domain\Copilot\Vector\VectorException;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use App\Domain\Copilot\Vector\VectorSearchResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class QdrantVectorIndex implements VectorIndex
{
    public function __construct(private array $config) {}

    public function ensureCollection(): void
    {
        $this->assertConfigured();

        $collection = $this->collection();
        $status = $this->request('GET', $this->url('/collections/'.$collection))->status();

        if ($status === 200) {
            $this->validateExistingCollection($this->request('GET', $this->url('/collections/'.$collection))->json());

            return;
        }

        if ($status !== 404) {
            throw $this->error($status, 'Failed to inspect collection.');
        }

        $create = $this->request('PUT', $this->url('/collections/'.$collection), [
            'vectors' => [
                'size' => $this->dimensions(),
                'distance' => $this->distance(),
            ],
        ]);

        if ($create->status() === 409) {
            $this->validateExistingCollection($this->request('GET', $this->url('/collections/'.$collection))->json());

            return;
        }

        if ($create->status() >= 400) {
            throw $this->error($create->status(), $create->body());
        }
    }

    public function upsert(VectorPoint $point): void
    {
        $this->assertConfigured();
        $this->validateId($point->id);
        $this->validateDimensions($point->vector);

        $response = $this->request('PUT', $this->url('/collections/'.$this->collection().'/points?wait=true'), [
            'points' => [
                [
                    'id' => $point->id,
                    'vector' => $point->vector,
                    'payload' => $point->payload,
                ],
            ],
        ]);

        if ($response->status() >= 400) {
            throw $this->error($response->status(), $response->body());
        }
    }

    public function delete(int $pointId): void
    {
        $this->assertConfigured();
        $this->validateId($pointId);

        $response = $this->request('POST', $this->url('/collections/'.$this->collection().'/points/delete'), [
            'points' => [$pointId],
        ]);

        if ($response->status() >= 400) {
            throw $this->error($response->status(), $response->body());
        }
    }

    public function search(array $vector, int $limit = 5): array
    {
        $this->assertConfigured();
        $this->validateDimensions($vector);

        $response = $this->request('POST', $this->url('/collections/'.$this->collection().'/points/query'), [
            'query' => $vector,
            'limit' => $limit,
            'with_payload' => true,
        ]);

        if ($response->status() >= 400) {
            throw $this->error($response->status(), $response->body());
        }

        $results = [];

        foreach ($response->json('result.points') ?? [] as $item) {
            $results[] = new VectorSearchResult(
                (int) ($item['id'] ?? 0),
                (float) ($item['score'] ?? 0.0),
                $item['payload'] ?? [],
            );
        }

        return $results;
    }

    public function isReady(): bool
    {
        try {
            if (! $this->request('GET', $this->url('/healthz'))->successful()) {
                return false;
            }

            $response = $this->request('GET', $this->url('/collections/'.$this->collection()));

            if ($response->status() !== 200) {
                return false;
            }

            return $this->configMatches($response->json());
        } catch (Throwable) {
            return false;
        }
    }

    private function request(string $method, string $url, ?array $body = null): Response
    {
        try {
            $client = $this->client();

            return match ($method) {
                'GET' => $client->get($url),
                'PUT' => $client->put($url, $body ?? []),
                'POST' => $client->post($url, $body ?? []),
                default => throw new VectorException('qdrant', 'Unsupported HTTP method.'),
            };
        } catch (ConnectionException) {
            throw new VectorException('qdrant', 'Connection to Qdrant failed.', null, true);
        }
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout((int) ($this->config['timeout'] ?? 30));

        $key = (string) ($this->config['api_key'] ?? '');

        if ($key !== '') {
            $client = $client->withHeaders(['api-key' => $key]);
        }

        return $client;
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->config['url'], '/').$path;
    }

    private function collection(): string
    {
        return (string) $this->config['collection'];
    }

    private function dimensions(): int
    {
        return (int) $this->config['dimensions'];
    }

    private function distance(): string
    {
        return (string) $this->config['distance'];
    }

    private function assertConfigured(): void
    {
        if (($this->config['url'] ?? '') === '' || ($this->config['collection'] ?? '') === '') {
            throw new VectorException('qdrant', 'Qdrant configuration is incomplete.');
        }
    }

    private function validateId(int $id): void
    {
        if ($id <= 0 || $id > PHP_INT_MAX) {
            throw new VectorException('qdrant', 'Invalid point id.');
        }
    }

    private function validateDimensions(array $vector): void
    {
        if (count($vector) !== $this->dimensions()) {
            throw new VectorException('qdrant', 'Invalid vector dimensions.');
        }
    }

    private function validateExistingCollection(?array $json): void
    {
        if (! $this->configMatches($json)) {
            throw new VectorException('qdrant', 'Existing collection configuration is incompatible.');
        }
    }

    private function configMatches(?array $json): bool
    {
        $vectors = $json['result']['config']['params']['vectors'] ?? null;

        if (! is_array($vectors)) {
            return false;
        }

        return (int) ($vectors['size'] ?? 0) === $this->dimensions()
            && (string) ($vectors['distance'] ?? '') === $this->distance();
    }

    private function error(int $status, string $body): VectorException
    {
        $retryable = in_array($status, [408, 429], true) || $status >= 500;

        return new VectorException(
            'qdrant',
            sprintf('Qdrant request failed (HTTP %d).', $status),
            $status,
            $retryable,
            $this->sanitize($body),
        );
    }

    private function sanitize(string $body): string
    {
        $body = str_replace((string) ($this->config['api_key'] ?? ''), '[REDACTED]', $body);

        return substr($body, 0, 500);
    }
}

<?php

namespace Tests\Feature\Vector;

use App\Domain\Copilot\Vector\VectorException;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Domain\Copilot\Vector\VectorPoint;
use App\Infrastructure\Copilot\Vector\QdrantVectorIndex;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QdrantVectorIndexTest extends TestCase
{
    private function index(): QdrantVectorIndex
    {
        return new QdrantVectorIndex([
            'url' => 'http://qdrant:6333',
            'api_key' => '',
            'collection' => 'government_chunks',
            'dimensions' => 768,
            'distance' => 'Cosine',
            'timeout' => 30,
        ]);
    }

    private function vector(): array
    {
        return array_fill(0, 768, 0.1);
    }

    private function collectionConfig(int $size = 768, string $distance = 'Cosine'): array
    {
        return ['result' => ['config' => ['params' => ['vectors' => ['size' => $size, 'distance' => $distance]]]]];
    }

    public function test_creates_missing_collection(): void
    {
        Http::fake(function ($request) {
            if ($request->method() === 'GET') {
                return Http::response([], 404);
            }

            return Http::response(['result' => true], 200);
        });

        $this->index()->ensureCollection();

        Http::assertSent(fn ($r) => $r->method() === 'PUT' && str_contains($r->url(), '/collections/government_chunks'));
    }

    public function test_accepts_existing_compatible_collection(): void
    {
        Http::fake(['*' => Http::response($this->collectionConfig(), 200)]);

        $this->index()->ensureCollection();

        Http::assertSentCount(2);
    }

    public function test_rejects_incompatible_dimension(): void
    {
        Http::fake(['*' => Http::response($this->collectionConfig(999), 200)]);

        $this->expectException(VectorException::class);
        $this->index()->ensureCollection();
    }

    public function test_rejects_incompatible_distance(): void
    {
        Http::fake(['*' => Http::response($this->collectionConfig(768, 'Dot'), 200)]);

        $this->expectException(VectorException::class);
        $this->index()->ensureCollection();
    }

    public function test_concurrent_creation_409_re_reads_and_validates(): void
    {
        $getCount = 0;
        Http::fake(function ($request) use (&$getCount) {
            if ($request->method() === 'GET') {
                $getCount++;

                return $getCount === 1 ? Http::response([], 404) : Http::response($this->collectionConfig(), 200);
            }
            if ($request->method() === 'PUT') {
                return Http::response(['error' => 'exists'], 409);
            }

            return Http::response([], 200);
        });

        $this->index()->ensureCollection();
        Http::assertSentCount(3);
    }

    public function test_upserts_point(): void
    {
        Http::fake(['*' => Http::response(['result' => ['status' => 'completed']], 200)]);

        $this->index()->upsert(new VectorPoint(123, $this->vector(), ['document_id' => 1, 'document_version_id' => 2]));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->method() === 'PUT'
                && str_contains($request->url(), '/collections/government_chunks/points?wait=true')
                && $body['points'][0]['id'] === 123
                && $body['points'][0]['payload']['document_id'] === 1;
        });
    }

    public function test_deletes_point(): void
    {
        Http::fake(['*' => Http::response(['result' => ['status' => 'completed']], 200)]);

        $this->index()->delete(123);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/points/delete')
                && $request->data()['points'] === [123];
        });
    }

    public function test_search_uses_query_api_and_maps_results(): void
    {
        Http::fake(['*' => Http::response([
            'result' => [
                'points' => [
                    ['id' => 1, 'score' => 0.9, 'payload' => ['document_id' => 5]],
                    ['id' => 2, 'score' => 0.5, 'payload' => ['document_id' => 6]],
                ],
            ],
        ], 200)]);

        $results = $this->index()->search($this->vector(), 5);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/points/query')
                && ! str_contains($request->url(), '/points/search')
                && is_array($body['query'])
                && count($body['query']) === 768
                && $body['limit'] === 5
                && $body['with_payload'] === true;
        });

        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]->id);
        $this->assertSame(0.9, $results[0]->score);
        $this->assertSame(5, $results[0]->payload['document_id']);
    }

    public function test_rejects_invalid_point_id_before_http(): void
    {
        Http::fake();

        try {
            $this->index()->upsert(new VectorPoint(0, $this->vector(), []));
            $this->fail('Expected invalid id.');
        } catch (VectorException $e) {
            $this->assertFalse($e->retryable);
        }

        Http::assertNothingSent();
    }

    public function test_rejects_invalid_vector_dimensions_before_http(): void
    {
        Http::fake();

        try {
            $this->index()->upsert(new VectorPoint(1, [0.1, 0.2], []));
            $this->fail('Expected invalid dimensions.');
        } catch (VectorException $e) {
            $this->assertFalse($e->retryable);
        }

        Http::assertNothingSent();
    }

    public function test_retryable_http_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

        try {
            $this->index()->upsert(new VectorPoint(1, $this->vector(), []));
            $this->fail('Expected error.');
        } catch (VectorException $e) {
            $this->assertTrue($e->retryable);
        }
    }

    public function test_non_retryable_http_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'bad'], 400)]);

        try {
            $this->index()->upsert(new VectorPoint(1, $this->vector(), []));
            $this->fail('Expected error.');
        } catch (VectorException $e) {
            $this->assertFalse($e->retryable);
            $this->assertSame(400, $e->statusCode);
        }
    }

    public function test_connection_failure_is_retryable(): void
    {
        Http::fake(fn () => throw new ConnectionException('refused'));

        try {
            $this->index()->upsert(new VectorPoint(1, $this->vector(), []));
            $this->fail('Expected connection error.');
        } catch (VectorException $e) {
            $this->assertTrue($e->retryable);
        }
    }

    public function test_is_ready_false_when_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('refused'));

        $this->assertFalse($this->index()->isReady());
    }

    public function test_is_ready_false_when_missing_or_incompatible(): void
    {
        Http::fake(['*' => Http::response([], 404)]);
        $this->assertFalse($this->index()->isReady());

        Http::fake(['*' => Http::response($this->collectionConfig(999), 200)]);
        $this->assertFalse($this->index()->isReady());
    }

    public function test_is_ready_true_when_healthy_and_compatible(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/healthz')) {
                return Http::response(['status' => 'ok'], 200);
            }

            return Http::response($this->collectionConfig(), 200);
        });

        $this->assertTrue($this->index()->isReady());
    }

    public function test_missing_configuration_is_non_retryable(): void
    {
        $index = new QdrantVectorIndex([
            'url' => '',
            'api_key' => '',
            'collection' => '',
            'dimensions' => 768,
            'distance' => 'Cosine',
            'timeout' => 30,
        ]);

        try {
            $index->ensureCollection();
            $this->fail('Expected config error.');
        } catch (VectorException $e) {
            $this->assertFalse($e->retryable);
        }

        $this->assertFalse($index->isReady());
    }

    public function test_dependency_injection_resolves_vector_index(): void
    {
        $this->assertInstanceOf(VectorIndex::class, app(VectorIndex::class));
        $this->assertInstanceOf(QdrantVectorIndex::class, app(VectorIndex::class));
    }
}

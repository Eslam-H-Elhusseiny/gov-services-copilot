<?php

namespace Tests\Feature\Embedding;

use App\Domain\Copilot\Embedding\EmbeddingException;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Infrastructure\Copilot\Embedding\GeminiEmbeddingProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingProviderTest extends TestCase
{
    private function provider(): GeminiEmbeddingProvider
    {
        return new GeminiEmbeddingProvider([
            'key' => 'secret-key',
            'model' => 'gemini-embedding-001',
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta',
            'dimensions' => 768,
            'task_type' => 'RETRIEVAL_DOCUMENT',
            'timeout' => 30,
        ]);
    }

    private function fakeEmbedding(int $dimensions = 768): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => array_fill(0, $dimensions, 0.1)],
            ]),
        ]);
    }

    public function test_request_mapping_uses_camelcase_config_and_header_auth(): void
    {
        $this->fakeEmbedding();

        $this->provider()->embed('Example document text');

        Http::assertSent(function ($request) {
            $body = $request->data();

            $this->assertStringEndsWith(':embedContent', $request->url());
            $this->assertStringNotContainsString('key=', $request->url());
            $this->assertSame('secret-key', $request->headers()['x-goog-api-key'][0] ?? null);
            $this->assertSame('Example document text', $body['content']['parts'][0]['text']);
            $this->assertSame('RETRIEVAL_DOCUMENT', $body['embedContentConfig']['taskType']);
            $this->assertSame(768, $body['embedContentConfig']['outputDimensionality']);

            return true;
        });
    }

    public function test_response_mapping_and_null_token_usage(): void
    {
        $this->fakeEmbedding();

        $result = $this->provider()->embed('text');

        $this->assertSame('gemini', $result->provider);
        $this->assertSame('gemini-embedding-001', $result->model);
        $this->assertSame(768, $result->dimensions);
        $this->assertCount(768, $result->vector);
        $this->assertNull($result->inputTokens);
    }

    public function test_dimension_mismatch_is_non_retryable(): void
    {
        $this->fakeEmbedding(10);

        try {
            $this->provider()->embed('text');
            $this->fail('Expected dimension mismatch.');
        } catch (EmbeddingException $e) {
            $this->assertFalse($e->retryable);
        }
    }

    public function test_empty_input_is_rejected_before_http(): void
    {
        Http::fake();

        $this->expectException(EmbeddingException::class);
        $this->provider()->embed('   ');

        Http::assertNothingSent();
    }

    public function test_retryable_errors(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500)]);
        try {
            $this->provider()->embed('text');
            $this->fail('Expected error.');
        } catch (EmbeddingException $e) {
            $this->assertTrue($e->retryable);
        }

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'rate'], 429)]);
        try {
            $this->provider()->embed('text');
            $this->fail('Expected error.');
        } catch (EmbeddingException $e) {
            $this->assertTrue($e->retryable);
        }
    }

    public function test_non_retryable_errors(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'unauthorized'], 401)]);
        try {
            $this->provider()->embed('text');
            $this->fail('Expected error.');
        } catch (EmbeddingException $e) {
            $this->assertFalse($e->retryable);
            $this->assertSame(401, $e->statusCode);
        }
    }

    public function test_missing_credentials_is_non_retryable_before_http(): void
    {
        Http::fake();

        $provider = new GeminiEmbeddingProvider([
            'key' => '',
            'model' => 'gemini-embedding-001',
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta',
            'dimensions' => 768,
            'task_type' => 'RETRIEVAL_DOCUMENT',
            'timeout' => 30,
        ]);

        try {
            $provider->embed('text');
            $this->fail('Expected missing key error.');
        } catch (EmbeddingException $e) {
            $this->assertFalse($e->retryable);
        }

        Http::assertNothingSent();
    }

    public function test_error_payload_is_sanitized_and_truncated(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'bad key secret-key '.str_repeat('a', 1000)], 400)]);

        try {
            $this->provider()->embed('text');
            $this->fail('Expected error.');
        } catch (EmbeddingException $e) {
            $this->assertStringNotContainsString('secret-key', (string) $e->raw);
            $this->assertStringContainsString('[REDACTED]', (string) $e->raw);
            $this->assertLessThanOrEqual(500, strlen((string) $e->raw));
        }
    }

    public function test_dependency_injection_resolves_embedding_provider(): void
    {
        $this->assertInstanceOf(EmbeddingProvider::class, app(EmbeddingProvider::class));
        $this->assertSame('gemini', app(EmbeddingProvider::class)->name());
    }
}

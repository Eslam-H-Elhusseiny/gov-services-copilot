<?php

namespace Tests\Feature\Llm;

use App\Application\Copilot\Llm\LlmProviderManager;
use App\Application\Copilot\Llm\StructuredOutputValidator;
use App\Domain\Copilot\Llm\LlmMessage;
use App\Domain\Copilot\Llm\LlmProvider;
use App\Domain\Copilot\Llm\LlmProviderException;
use App\Domain\Copilot\Llm\LlmRequest;
use App\Domain\Copilot\Llm\LlmTool;
use App\Domain\Copilot\Llm\ToolCall;
use App\Infrastructure\Copilot\Llm\GeminiProvider;
use App\Infrastructure\Copilot\Llm\GroqProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderArchitectureTest extends TestCase
{
    private function textRequest(): LlmRequest
    {
        return new LlmRequest('model-x', [new LlmMessage('user', 'hello')]);
    }

    private function toolRequest(): LlmRequest
    {
        return new LlmRequest('model-x', [new LlmMessage('user', 'weather?')], [
            new LlmTool('get_weather', 'Get weather', ['type' => 'object', 'properties' => []]),
        ]);
    }

    private function structuredRequest(): LlmRequest
    {
        return new LlmRequest('openai/gpt-oss-120b', [new LlmMessage('user', 'extract')], [], [
            'type' => 'object',
            'properties' => ['name' => ['type' => 'string']],
            'required' => ['name'],
        ]);
    }

    private function comboRequest(): LlmRequest
    {
        return new LlmRequest('model-x', [new LlmMessage('user', 'combo')], [
            new LlmTool('t', 'd', ['type' => 'object']),
        ], ['type' => 'object', 'properties' => []]);
    }

    public function test_providers_accept_text_only_requests(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'hi'], 'finish_reason' => 'stop']], 'usage' => ['input_tokens' => 1, 'output_tokens' => 2]]),
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'hi']]], 'finishReason' => 'STOP']], 'usageMetadata' => ['promptTokenCount' => 1, 'candidatesTokenCount' => 2]]),
        ]);

        $this->assertTrue(app(GroqProvider::class)->supportsRequest($this->textRequest()));
        $this->assertSame('hi', app(GroqProvider::class)->generate($this->textRequest())->text);
        $this->assertTrue(app(GeminiProvider::class)->supportsRequest($this->textRequest()));
        $this->assertSame('hi', app(GeminiProvider::class)->generate($this->textRequest())->text);
    }

    public function test_providers_accept_tool_only_requests(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['tool_calls' => [['id' => 'c1', 'function' => ['name' => 'get_weather', 'arguments' => '{"city":"x"}']]]], 'finish_reason' => 'tool_calls']]]),
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'get_weather', 'args' => ['city' => 'x']]]]], 'finishReason' => 'STOP']]]),
        ]);

        $this->assertTrue(app(GroqProvider::class)->supportsRequest($this->toolRequest()));
        $this->assertTrue(app(GeminiProvider::class)->supportsRequest($this->toolRequest()));
        $this->assertCount(1, app(GroqProvider::class)->generate($this->toolRequest())->toolCalls);
        $this->assertCount(1, app(GeminiProvider::class)->generate($this->toolRequest())->toolCalls);
    }

    public function test_providers_accept_structured_output_only_requests(): void
    {
        $groq = app(GroqProvider::class);
        $gemini = app(GeminiProvider::class);

        $this->assertTrue($groq->supportsRequest($this->structuredRequest()));
        $this->assertTrue($gemini->supportsRequest($this->structuredRequest()));
    }

    public function test_providers_reject_tool_and_structured_combination(): void
    {
        $this->assertFalse(app(GroqProvider::class)->supportsRequest($this->comboRequest()));
        $this->assertFalse(app(GeminiProvider::class)->supportsRequest($this->comboRequest()));
    }

    public function test_unsupported_requests_fail_before_http(): void
    {
        Http::fake();

        try {
            app(GroqProvider::class)->generate($this->comboRequest());
            $this->fail('Expected Groq to reject combo request.');
        } catch (LlmProviderException $e) {
            $this->assertFalse($e->retryable);
        }

        try {
            app(GeminiProvider::class)->generate($this->comboRequest());
            $this->fail('Expected Gemini to reject combo request.');
        } catch (LlmProviderException $e) {
            $this->assertFalse($e->retryable);
        }

        Http::assertNothingSent();
    }

    public function test_groq_maps_request_and_response_with_previous_tool_calls(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'final'], 'finish_reason' => 'stop']], 'usage' => ['input_tokens' => 3, 'output_tokens' => 4]]),
        ]);

        $request = new LlmRequest('model-x', [
            new LlmMessage('user', 'weather?'),
            new LlmMessage('assistant', null, [new ToolCall('c1', 'get_weather', '{"city":"x"}')]),
            new LlmMessage('tool', '{"temp":72}', null, 'c1'),
        ]);

        $response = app(GroqProvider::class)->generate($request);

        $this->assertSame('final', $response->text);

        Http::assertSent(function ($httpRequest) {
            $body = $httpRequest->data();
            $this->assertSame('tool', $body['messages'][2]['role']);
            $this->assertSame('c1', $body['messages'][2]['tool_call_id']);
            $this->assertSame('tool_calls', $body['messages'][1]['role'] === 'assistant' && isset($body['messages'][1]['tool_calls']) ? 'tool_calls' : 'no');

            return true;
        });
    }

    public function test_gemini_maps_multiple_response_parts(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'thinking'], ['functionCall' => ['name' => 'f', 'args' => ['a' => 1]]]]], 'finishReason' => 'STOP']], 'usageMetadata' => ['promptTokenCount' => 5, 'candidatesTokenCount' => 6]]),
        ]);

        $response = app(GeminiProvider::class)->generate($this->textRequest());

        $this->assertSame('thinking', $response->text);
        $this->assertCount(1, $response->toolCalls);
        $this->assertSame('f', $response->toolCalls[0]->name);
    }

    public function test_tool_calls_are_mapped_but_not_executed(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['tool_calls' => [['id' => 'c9', 'function' => ['name' => 'side_effect', 'arguments' => '{}']]]], 'finish_reason' => 'tool_calls']]]),
        ]);

        $response = app(GroqProvider::class)->generate($this->toolRequest());

        $this->assertInstanceOf(ToolCall::class, $response->toolCalls[0]);
        $this->assertSame('side_effect', $response->toolCalls[0]->name);
        $this->assertNull($response->text);
    }

    public function test_nullable_text_for_tool_only_response(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['tool_calls' => [['id' => 'c', 'function' => ['name' => 'f', 'arguments' => '{}']]]], 'finish_reason' => 'tool_calls']]]),
        ]);

        $this->assertNull(app(GroqProvider::class)->generate($this->toolRequest())->text);
    }

    public function test_token_usage_maps_correctly(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'x'], 'finish_reason' => 'stop']], 'usage' => ['input_tokens' => 7, 'output_tokens' => 8]]),
        ]);

        $response = app(GroqProvider::class)->generate($this->textRequest());

        $this->assertSame(7, $response->usage->inputTokens);
        $this->assertSame(8, $response->usage->outputTokens);
    }

    public function test_missing_usage_is_null_not_zero(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'x'], 'finish_reason' => 'stop']]]),
        ]);

        $response = app(GroqProvider::class)->generate($this->textRequest());

        $this->assertNull($response->usage);
    }

    public function test_structured_output_validation_accepts_valid_and_rejects_invalid(): void
    {
        $schema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']], 'required' => ['name', 'age']];

        $valid = StructuredOutputValidator::validate('{"name":"Ahmed","age":30}', $schema);
        $this->assertSame('Ahmed', $valid['name']);

        $this->expectException(LlmProviderException::class);
        StructuredOutputValidator::validate('{"name":5}', $schema);
    }

    public function test_retryable_failure_triggers_fallback_once(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'boom'], 500),
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]], 'finishReason' => 'STOP']]], 200),
        ]);

        $response = app(LlmProviderManager::class)->generate($this->textRequest());

        $this->assertSame('gemini', $response->provider);
        Http::assertSentCount(2);
    }

    public function test_non_retryable_error_does_not_trigger_fallback(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'unauthorized'], 401),
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]], 'finishReason' => 'STOP']]], 200),
        ]);

        try {
            app(LlmProviderManager::class)->generate($this->textRequest());
            $this->fail('Expected non-retryable error to propagate.');
        } catch (LlmProviderException $e) {
            $this->assertSame(401, $e->statusCode);
        }

        Http::assertSentCount(1);
    }

    public function test_error_payload_is_sanitized_and_truncated(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'invalid key sk-secret123 '.str_repeat('a', 1000)], 400),
        ]);

        $provider = new GroqProvider([
            'key' => 'sk-secret123',
            'model' => 'openai/gpt-oss-120b',
            'base_uri' => 'https://api.groq.com/openai/v1',
            'timeout' => 30,
        ]);

        try {
            $provider->generate($this->textRequest());
            $this->fail('Expected error.');
        } catch (LlmProviderException $e) {
            $this->assertStringNotContainsString('sk-secret123', (string) $e->raw);
            $this->assertStringContainsString('[REDACTED]', (string) $e->raw);
            $this->assertLessThanOrEqual(500, strlen((string) $e->raw));
        }
    }

    public function test_dependency_injection_resolves_manager_and_providers(): void
    {
        $this->assertInstanceOf(LlmProviderManager::class, app(LlmProviderManager::class));
        $this->assertSame('groq', app(GroqProvider::class)->name());
        $this->assertSame('gemini', app(GeminiProvider::class)->name());
        $this->assertInstanceOf(LlmProvider::class, app(GroqProvider::class));
    }
}

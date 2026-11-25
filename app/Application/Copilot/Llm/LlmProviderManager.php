<?php

namespace App\Application\Copilot\Llm;

use App\Domain\Copilot\Llm\LlmProvider;
use App\Domain\Copilot\Llm\LlmProviderException;
use App\Domain\Copilot\Llm\LlmRequest;
use App\Domain\Copilot\Llm\LlmResponse;

final class LlmProviderManager
{
    /**
     * @param  array<int, LlmProvider>  $providers
     */
    public function __construct(
        private array $providers,
        private string $default,
        private string $fallback,
    ) {}

    public function generate(LlmRequest $request): LlmResponse
    {
        $primary = $this->resolve($this->default);

        try {
            return $primary->generate($request);
        } catch (LlmProviderException $exception) {
            if (! $exception->retryable) {
                throw $exception;
            }
        }

        $fallback = $this->resolve($this->fallback);

        return $fallback->generate($request);
    }

    private function resolve(string $name): LlmProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->name() === $name) {
                return $provider;
            }
        }

        throw new LlmProviderException('manager', sprintf('Provider [%s] is not configured.', $name));
    }
}

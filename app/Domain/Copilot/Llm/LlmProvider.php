<?php

namespace App\Domain\Copilot\Llm;

interface LlmProvider
{
    public function name(): string;

    public function supports(LlmCapability $capability, ?string $model = null): bool;

    public function supportsRequest(LlmRequest $request): bool;

    /**
     * @throws LlmProviderException
     */
    public function generate(LlmRequest $request): LlmResponse;
}

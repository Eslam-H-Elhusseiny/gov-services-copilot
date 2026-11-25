<?php

namespace App\Domain\Copilot\Embedding;

interface EmbeddingProvider
{
    public function name(): string;

    public function dimensions(): int;

    /**
     * @throws EmbeddingException
     */
    public function embed(string $text): EmbeddingResult;
}

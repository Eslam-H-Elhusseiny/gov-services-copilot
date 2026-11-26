<?php

namespace App\Domain\Copilot\Retrieval;

interface KeywordSearch
{
    /**
     * Ordered by descending relevance score; rank is one-based.
     *
     * @return array<int, array{id: int, score: float, rank: int}>
     */
    public function search(string $query, int $limit): array;
}

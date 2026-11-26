<?php

namespace App\Domain\Copilot\Vector;

interface VectorIndex
{
    public function ensureCollection(): void;

    public function upsert(VectorPoint $point): void;

    public function delete(int $pointId): void;

    /**
     * @param  array<int, float>  $vector
     * @return array<int, VectorSearchResult>
     */
    public function search(array $vector, int $limit = 5): array;

    public function isReady(): bool;
}

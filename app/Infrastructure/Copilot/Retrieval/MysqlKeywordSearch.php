<?php

namespace App\Infrastructure\Copilot\Retrieval;

use App\Domain\Copilot\Retrieval\KeywordSearch;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\DB;

final class MysqlKeywordSearch implements KeywordSearch
{
    public function search(string $query, int $limit): array
    {
        $query = trim($query);

        if ($query === '' || DB::getDriverName() !== 'mysql') {
            return [];
        }

        $rows = DocumentChunk::query()
            ->select('id')
            ->selectRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE) AS score', [$query])
            ->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$query])
            ->orderByDesc('score')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $results = [];
        $rank = 1;

        foreach ($rows as $row) {
            $results[] = [
                'id' => (int) $row->id,
                'score' => (float) $row->score,
                'rank' => $rank++,
            ];
        }

        return $results;
    }
}

<?php

namespace App\Domain\Copilot\Retrieval;

final class RrfFuser
{
    /**
     * @param  array<int, array{id: int, score: float, rank: int}>  $dense
     * @param  array<int, array{id: int, score: float, rank: int}>  $keyword
     * @return array<int, array{id: int, rrfScore: float, sources: string, rank: int}>
     */
    public function fuse(array $dense, array $keyword, int $k = 60): array
    {
        if ($k < 1) {
            throw new \InvalidArgumentException('The RRF constant k must be greater than or equal to 1.');
        }

        $map = [];

        foreach (['dense' => $dense, 'keyword' => $keyword] as $source => $list) {
            $bestRanks = [];

            foreach ($list as $item) {
                $id = (int) ($item['id'] ?? 0);
                $rank = (int) ($item['rank'] ?? 0);

                if ($id <= 0 || $rank <= 0) {
                    continue;
                }

                $bestRanks[$id] = min($bestRanks[$id] ?? $rank, $rank);
            }

            foreach ($bestRanks as $id => $rank) {
                $map[$id] ??= ['id' => $id, 'rrfScore' => 0.0, 'dense' => false, 'keyword' => false, 'bestRank' => $rank];
                $map[$id]['rrfScore'] += 1 / ($k + $rank);
                $map[$id][$source] = true;
                $map[$id]['bestRank'] = min($map[$id]['bestRank'], $rank);
            }
        }

        $result = [];

        foreach ($map as $entry) {
            $sources = match (true) {
                $entry['dense'] && $entry['keyword'] => 'both',
                $entry['dense'] => 'dense',
                default => 'keyword',
            };

            $result[] = [
                'id' => $entry['id'],
                'rrfScore' => $entry['rrfScore'],
                'sources' => $sources,
                'rank' => $entry['bestRank'],
            ];
        }

        usort($result, static fn (array $a, array $b) => $b['rrfScore'] <=> $a['rrfScore'] ?: $a['id'] <=> $b['id']);

        foreach ($result as $index => $entry) {
            $result[$index]['rank'] = $index + 1;
        }

        return $result;
    }
}

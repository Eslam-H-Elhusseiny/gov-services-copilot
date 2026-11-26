<?php

namespace Tests\Unit;

use App\Domain\Copilot\Retrieval\RrfFuser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RrfFuserTest extends TestCase
{
    private function fuser(): RrfFuser
    {
        return new RrfFuser;
    }

    public function test_exact_rrf_calculation_with_k_60(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 1, 'score' => 0.9, 'rank' => 1]],
            [['id' => 2, 'score' => 0.8, 'rank' => 1]],
        );

        $this->assertCount(2, $result);
        $this->assertEqualsWithDelta(1 / 61, $result[0]['rrfScore'], 1e-9);
        $this->assertEqualsWithDelta(1 / 61, $result[1]['rrfScore'], 1e-9);
    }

    public function test_dense_and_keyword_merge_by_id(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 1, 'score' => 0.9, 'rank' => 1]],
            [['id' => 1, 'score' => 0.8, 'rank' => 2]],
        );

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertEqualsWithDelta(1 / 61 + 1 / 62, $result[0]['rrfScore'], 1e-9);
    }

    public function test_source_tracking(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 1, 'score' => 0.9, 'rank' => 1]],
            [['id' => 2, 'score' => 0.8, 'rank' => 1], ['id' => 1, 'score' => 0.7, 'rank' => 3]],
        );

        $byId = [];
        foreach ($result as $entry) {
            $byId[$entry['id']] = $entry['sources'];
        }

        $this->assertSame('both', $byId[1]);
        $this->assertSame('keyword', $byId[2]);
    }

    public function test_single_retriever_results_are_kept(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 1, 'score' => 0.9, 'rank' => 1]],
            [],
        );

        $this->assertCount(1, $result);
        $this->assertSame('dense', $result[0]['sources']);
    }

    public function test_deterministic_tie_break_by_ascending_id(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 2, 'score' => 0.5, 'rank' => 1], ['id' => 1, 'score' => 0.5, 'rank' => 1]],
            [],
        );

        $this->assertSame([1, 2], array_column($result, 'id'));
    }

    public function test_final_ranks_are_sequential(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 3, 'score' => 0.5, 'rank' => 1], ['id' => 1, 'score' => 0.5, 'rank' => 2], ['id' => 2, 'score' => 0.5, 'rank' => 3]],
            [],
        );

        $this->assertSame([1, 2, 3], array_column($result, 'rank'));
    }

    public function test_empty_lists_return_empty(): void
    {
        $this->assertSame([], $this->fuser()->fuse([], []));
    }

    public function test_invalid_ids_and_ranks_are_skipped(): void
    {
        $result = $this->fuser()->fuse(
            [
                ['id' => 0, 'score' => 0.5, 'rank' => 1],
                ['id' => 1, 'score' => 0.5, 'rank' => 0],
                ['id' => 1, 'score' => 0.5, 'rank' => -1],
                ['id' => 2, 'score' => 0.5, 'rank' => 1],
            ],
            [],
        );

        $this->assertSame([2], array_column($result, 'id'));
    }

    public function test_duplicate_ids_within_one_retriever_counted_once(): void
    {
        $result = $this->fuser()->fuse(
            [['id' => 1, 'score' => 0.9, 'rank' => 5], ['id' => 1, 'score' => 0.9, 'rank' => 2]],
            [],
        );

        $this->assertCount(1, $result);
        $this->assertEqualsWithDelta(1 / 62, $result[0]['rrfScore'], 1e-9);
    }

    public function test_invalid_k_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->fuser()->fuse([], [], 0);
    }
}

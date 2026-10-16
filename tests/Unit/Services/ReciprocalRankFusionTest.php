<?php

namespace Tests\Unit\Services;

use App\Services\ReciprocalRankFusion;
use PHPUnit\Framework\TestCase;

class ReciprocalRankFusionTest extends TestCase
{
    public function test_combines_ranked_lists_with_rank_constant_60(): void
    {
        $results = (new ReciprocalRankFusion)->fuse([
            'keyword' => [
                ['id' => 10, 'rank' => 1],
                ['id' => 20, 'rank' => 2],
            ],
            'semantic' => [
                ['id' => 20, 'rank' => 1],
                ['id' => 30, 'rank' => 2],
            ],
        ], 3);

        $this->assertSame([20, 10, 30], array_column($results, 'id'));
        $this->assertSame(['keyword' => 2, 'semantic' => 1], $results[0]['ranks']);
        $this->assertEqualsWithDelta((1 / 62) + (1 / 61), $results[0]['score'], PHP_FLOAT_EPSILON);
    }

    public function test_applies_result_limit_after_fusion(): void
    {
        $results = (new ReciprocalRankFusion)->fuse([
            'keyword' => [
                ['id' => 1, 'rank' => 1],
                ['id' => 2, 'rank' => 2],
            ],
            'semantic' => [
                ['id' => 2, 'rank' => 1],
            ],
        ], 1);

        $this->assertSame([2], array_column($results, 'id'));
    }
}

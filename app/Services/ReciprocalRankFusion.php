<?php

namespace App\Services;

class ReciprocalRankFusion
{
    public const int RANK_CONSTANT = 60;

    /**
     * @param  array<string, list<array{id: int, rank: int}>>  $rankedLists
     * @return list<array{id: int, score: float, ranks: array<string, int>}>
     */
    public function fuse(array $rankedLists, int $limit): array
    {
        $fused = [];

        foreach ($rankedLists as $listName => $rankedItems) {
            foreach ($rankedItems as $item) {
                $documentId = $item['id'];
                $rank = $item['rank'];

                $fused[$documentId] ??= [
                    'id' => $documentId,
                    'score' => 0.0,
                    'ranks' => [],
                ];

                $fused[$documentId]['score'] += 1 / (self::RANK_CONSTANT + $rank);
                $fused[$documentId]['ranks'][$listName] = $rank;
            }
        }

        $results = array_values($fused);

        usort($results, static function (array $left, array $right): int {
            $scoreComparison = $right['score'] <=> $left['score'];

            return $scoreComparison !== 0
                ? $scoreComparison
                : $left['id'] <=> $right['id'];
        });

        return array_slice($results, 0, $limit);
    }
}

<?php

namespace App\Services;

use App\Contracts\EmbeddingGenerator;
use App\Models\Document;
use App\Repositories\DocumentRepository;

class HybridSearchService
{
    private const int MINIMUM_CANDIDATES = 50;

    public function __construct(
        private EmbeddingGenerator $embeddingGenerator,
        private DocumentRepository $documents,
        private ReciprocalRankFusion $fusion,
    ) {}

    /**
     * @return list<array{document: Document, rrf_score: float, keyword_rank: ?int, semantic_rank: ?int}>
     */
    public function search(string $query, int $limit): array
    {
        $candidateLimit = max(self::MINIMUM_CANDIDATES, $limit * 5);
        $embedding = $this->embeddingGenerator->generate($query);

        $fused = $this->fusion->fuse([
            'keyword' => $this->documents->keywordRanks($query, $candidateLimit),
            'semantic' => $this->documents->semanticRanks($embedding, $candidateLimit),
        ], $limit);

        $documents = $this->documents->findByIds(array_column($fused, 'id'));
        $results = [];

        foreach ($fused as $result) {
            $document = $documents->get($result['id']);

            if ($document === null) {
                continue;
            }

            $results[] = [
                'document' => $document,
                'rrf_score' => $result['score'],
                'keyword_rank' => $result['ranks']['keyword'] ?? null,
                'semantic_rank' => $result['ranks']['semantic'] ?? null,
            ];
        }

        return $results;
    }
}

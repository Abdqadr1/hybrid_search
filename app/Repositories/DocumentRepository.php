<?php

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentRepository
{
    /**
     * @param  list<float>  $embedding
     */
    public function create(string $title, string $content, array $embedding): Document
    {
        if ($this->usesPostgres()) {
            $row = DB::selectOne(
                <<<'SQL'
                    INSERT INTO documents (title, content, embedding, created_at, updated_at)
                    VALUES (?, ?, CAST(? AS vector), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    RETURNING id
                SQL,
                [$title, $content, $this->vectorLiteral($embedding)],
            );

            if ($row === null) {
                throw new RuntimeException('The document could not be created.');
            }

            return Document::query()->findOrFail((int) $row->id);
        }

        $documentId = DB::table('documents')->insertGetId([
            'title' => $title,
            'content' => $content,
            'embedding' => json_encode($embedding, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Document::query()->findOrFail($documentId);
    }

    /**
     * @return list<array{id: int, rank: int}>
     */
    public function keywordRanks(string $query, int $limit): array
    {
        if (! $this->usesPostgres()) {
            return Document::query()
                ->select('id')
                ->where(function ($builder) use ($query): void {
                    $builder
                        ->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($query).'%'])
                        ->orWhereRaw('LOWER(content) LIKE ?', ['%'.mb_strtolower($query).'%']);
                })
                ->orderBy('id')
                ->limit($limit)
                ->pluck('id')
                ->values()
                ->map(fn (int $id, int $index): array => ['id' => $id, 'rank' => $index + 1])
                ->all();
        }

        $rows = DB::select(
            <<<'SQL'
                WITH search_query AS (
                    SELECT websearch_to_tsquery('english', ?) AS value
                ), ranked AS (
                    SELECT documents.id,
                           ts_rank_cd(documents.search_vector, search_query.value) AS keyword_score
                    FROM documents
                    CROSS JOIN search_query
                    WHERE documents.search_vector @@ search_query.value
                    ORDER BY keyword_score DESC, documents.id ASC
                    LIMIT ?
                )
                SELECT id, ROW_NUMBER() OVER (ORDER BY keyword_score DESC, id ASC) AS rank
                FROM ranked
                ORDER BY rank
            SQL,
            [$query, $limit],
        );

        return array_map(
            static fn (object $row): array => ['id' => (int) $row->id, 'rank' => (int) $row->rank],
            $rows,
        );
    }

    /**
     * @param  list<float>  $embedding
     * @return list<array{id: int, rank: int}>
     */
    public function semanticRanks(array $embedding, int $limit): array
    {
        if (! $this->usesPostgres()) {
            $queryVector = $embedding;
            $ranked = Document::query()
                ->whereNotNull('embedding')
                ->get(['id', 'embedding'])
                ->map(function (Document $document) use ($queryVector): array {
                    $documentVector = json_decode((string) $document->getRawOriginal('embedding'), true, flags: JSON_THROW_ON_ERROR);

                    return [
                        'id' => $document->getKey(),
                        'distance' => $this->cosineDistance($queryVector, $documentVector),
                    ];
                })
                ->sortBy([['distance', 'asc'], ['id', 'asc']])
                ->take($limit)
                ->values();

            return $ranked
                ->map(fn (array $item, int $index): array => ['id' => $item['id'], 'rank' => $index + 1])
                ->all();
        }

        $rows = DB::select(
            <<<'SQL'
                WITH ranked AS (
                    SELECT id, embedding <=> CAST(? AS vector) AS distance
                    FROM documents
                    WHERE embedding IS NOT NULL
                    ORDER BY distance ASC, id ASC
                    LIMIT ?
                )
                SELECT id, ROW_NUMBER() OVER (ORDER BY distance ASC, id ASC) AS rank
                FROM ranked
                ORDER BY rank
            SQL,
            [$this->vectorLiteral($embedding), $limit],
        );

        return array_map(
            static fn (object $row): array => ['id' => (int) $row->id, 'rank' => (int) $row->rank],
            $rows,
        );
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Document>
     */
    public function findByIds(array $ids): Collection
    {
        return Document::query()
            ->whereIn('id', $ids)
            ->get(['id', 'title', 'content', 'created_at', 'updated_at'])
            ->keyBy(fn (Document $document): int => $document->getKey());
    }

    private function usesPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * @param  list<float>  $embedding
     */
    private function vectorLiteral(array $embedding): string
    {
        return '['.implode(',', array_map(
            static fn (float $value): string => (string) $value,
            $embedding,
        )).']';
    }

    /**
     * @param  list<float>  $left
     * @param  list<float>  $right
     */
    private function cosineDistance(array $left, array $right): float
    {
        $dotProduct = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach ($left as $index => $leftValue) {
            $rightValue = (float) ($right[$index] ?? 0.0);
            $dotProduct += $leftValue * $rightValue;
            $leftMagnitude += $leftValue ** 2;
            $rightMagnitude += $rightValue ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return 1.0;
        }

        return 1 - ($dotProduct / (sqrt($leftMagnitude) * sqrt($rightMagnitude)));
    }
}

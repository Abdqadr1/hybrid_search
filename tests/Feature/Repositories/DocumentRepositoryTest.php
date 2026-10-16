<?php

namespace Tests\Feature\Repositories;

use App\Repositories\DocumentRepository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DocumentRepositoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_postgres_ranks_keyword_and_semantic_matches(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('This test requires PostgreSQL with pgvector.');
        }

        $documents = app(DocumentRepository::class);
        $laravel = $documents->create(
            'Laravel queues',
            'Background workers process queued jobs.',
            $this->embedding(1.0, 0.0),
        );
        $postgres = $documents->create(
            'PostgreSQL indexes',
            'GIN and HNSW indexes serve different query types.',
            $this->embedding(0.8, 0.2),
        );
        $cooking = $documents->create(
            'Cooking notes',
            'A recipe for soup.',
            $this->embedding(0.0, 1.0),
        );

        $keywordRanks = $documents->keywordRanks('Laravel background', 10);
        $semanticRanks = $documents->semanticRanks($this->embedding(1.0, 0.0), 10);

        $this->assertSame([
            ['id' => $laravel->getKey(), 'rank' => 1],
        ], $keywordRanks);
        $this->assertSame([
            ['id' => $laravel->getKey(), 'rank' => 1],
            ['id' => $postgres->getKey(), 'rank' => 2],
            ['id' => $cooking->getKey(), 'rank' => 3],
        ], $semanticRanks);
    }

    /**
     * @return list<float>
     */
    private function embedding(float $first, float $second): array
    {
        return [$first, $second, ...array_fill(0, 510, 0.0)];
    }
}

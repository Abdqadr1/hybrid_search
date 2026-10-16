<?php

namespace Tests\Feature\Api;

use App\Repositories\DocumentRepository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchDocumentsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_query_returns_rrf_ranked_keyword_and_semantic_results(): void
    {
        $documents = app(DocumentRepository::class);
        $laravel = $documents->create(
            'Laravel queues',
            'Laravel queues run work in the background.',
            $this->embedding(1.0, 0.0),
        );
        $postgres = $documents->create(
            'PostgreSQL indexes',
            'GIN and HNSW indexes serve different query types.',
            $this->embedding(0.8, 0.2),
        );
        $documents->create(
            'Cooking notes',
            'A recipe for vegetable soup.',
            $this->embedding(0.0, 1.0),
        );

        config(['services.openai.api_key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => $this->embedding(1.0, 0.0)]],
            ]),
        ]);

        $response = $this->getJson(route('search.index', [
            'q' => 'Laravel',
            'limit' => 2,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $laravel->getKey())
            ->assertJsonPath('data.0.ranks.keyword', 1)
            ->assertJsonPath('data.0.ranks.semantic', 1)
            ->assertJsonPath('data.1.id', $postgres->getKey())
            ->assertJsonPath('data.1.ranks.keyword', null)
            ->assertJsonPath('data.1.ranks.semantic', 2)
            ->assertJsonPath('meta.query', 'Laravel')
            ->assertJsonPath('meta.limit', 2);
    }

    public function test_missing_query_returns_422_without_calling_embedding_provider(): void
    {
        Http::preventStrayRequests();

        $response = $this->getJson(route('search.index'));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q'])
            ->assertJsonPath('errors.q.0', 'The q field is required.');

        Http::assertNothingSent();
    }

    public function test_limit_above_maximum_returns_422_without_calling_embedding_provider(): void
    {
        Http::preventStrayRequests();

        $response = $this->getJson(route('search.index', [
            'q' => 'Laravel',
            'limit' => 51,
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit'])
            ->assertJsonPath('errors.limit.0', 'The limit field must be between 1 and 50.');

        Http::assertNothingSent();
    }

    /**
     * @return list<float>
     */
    private function embedding(float $first, float $second): array
    {
        return [$first, $second, ...array_fill(0, 510, 0.0)];
    }
}

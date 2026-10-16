<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreDocumentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_payload_creates_document_and_returns_201(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => $this->embedding(1.0, 0.0)]],
            ]),
        ]);

        $response = $this->postJson(route('documents.store'), [
            'title' => 'Laravel search',
            'content' => 'A document about full-text and semantic search.',
            'embedding' => ['must' => 'be ignored'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Laravel search')
            ->assertJsonPath('data.content', 'A document about full-text and semantic search.');

        $this->assertDatabaseHas('documents', [
            'title' => 'Laravel search',
            'content' => 'A document about full-text and semantic search.',
        ]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/embeddings'
            && $request['input'] === "Laravel search\n\nA document about full-text and semantic search."
            && $request['model'] === 'text-embedding-3-small'
            && $request['dimensions'] === 512
            && $request->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_empty_payload_returns_422_and_creates_no_document(): void
    {
        Http::preventStrayRequests();

        $response = $this->postJson(route('documents.store'));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content'])
            ->assertJsonPath('errors.title.0', 'The title field is required.')
            ->assertJsonPath('errors.content.0', 'The content field is required.');

        $this->assertDatabaseCount('documents', 0);
        Http::assertNothingSent();
    }

    public function test_embedding_provider_failure_returns_502_and_creates_no_document(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response(['error' => ['message' => 'Unavailable']], 503),
        ]);

        $response = $this->postJson(route('documents.store'), [
            'title' => 'Unavailable embedding',
            'content' => 'This should not be stored.',
        ]);

        $response
            ->assertStatus(502)
            ->assertExactJson(['message' => 'The embedding service is temporarily unavailable.']);

        $this->assertDatabaseCount('documents', 0);
    }

    /**
     * @return list<float>
     */
    private function embedding(float $first, float $second): array
    {
        return [$first, $second, ...array_fill(0, 510, 0.0)];
    }
}

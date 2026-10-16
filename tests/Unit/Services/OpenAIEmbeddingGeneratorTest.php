<?php

namespace Tests\Unit\Services;

use App\Exceptions\EmbeddingGenerationException;
use App\Services\OpenAIEmbeddingGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIEmbeddingGeneratorTest extends TestCase
{
    public function test_returns_512_dimension_embedding_from_openai(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        $expected = array_fill(0, 512, 0.25);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => $expected]],
            ]),
        ]);

        $embedding = app(OpenAIEmbeddingGenerator::class)->generate('Search me');

        $this->assertSame($expected, $embedding);
        Http::assertSent(fn (Request $request): bool => $request['input'] === 'Search me'
            && $request['model'] === 'text-embedding-3-small'
            && $request['dimensions'] === 512);
    }

    public function test_rejects_embedding_with_wrong_dimensions(): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => [0.1, 0.2]]],
            ]),
        ]);

        $this->expectException(EmbeddingGenerationException::class);

        app(OpenAIEmbeddingGenerator::class)->generate('Wrong size');
    }
}

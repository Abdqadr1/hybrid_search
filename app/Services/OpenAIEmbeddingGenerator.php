<?php

namespace App\Services;

use App\Contracts\EmbeddingGenerator;
use App\Exceptions\EmbeddingGenerationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIEmbeddingGenerator implements EmbeddingGenerator
{
    /**
     * @return list<float>
     */
    public function generate(string $input): array
    {
        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new EmbeddingGenerationException;
        }

        try {
            $response = Http::baseUrl((string) config('services.openai.base_url'))
                ->withToken($apiKey)
                ->acceptJson()
                ->connectTimeout((int) config('services.openai.connect_timeout'))
                ->timeout((int) config('services.openai.timeout'))
                ->post('/embeddings', [
                    'input' => $input,
                    'model' => config('services.openai.embedding_model'),
                    'dimensions' => (int) config('services.openai.embedding_dimensions'),
                    'encoding_format' => 'float',
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new EmbeddingGenerationException($exception);
        }

        $embedding = $response->json('data.0.embedding');
        $dimensions = (int) config('services.openai.embedding_dimensions');

        if (! is_array($embedding) || count($embedding) !== $dimensions) {
            throw new EmbeddingGenerationException;
        }

        try {
            return array_values(array_map(function (mixed $value): float {
                if (! is_numeric($value) || ! is_finite((float) $value)) {
                    throw new EmbeddingGenerationException;
                }

                return (float) $value;
            }, $embedding));
        } catch (EmbeddingGenerationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new EmbeddingGenerationException($exception);
        }
    }
}

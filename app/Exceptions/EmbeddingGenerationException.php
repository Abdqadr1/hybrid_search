<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class EmbeddingGenerationException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('The embedding service is unavailable.', previous: $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'The embedding service is temporarily unavailable.',
        ], 502);
    }
}

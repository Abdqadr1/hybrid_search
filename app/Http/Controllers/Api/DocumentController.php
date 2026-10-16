<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Services\DocumentIndexer;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, DocumentIndexer $indexer): JsonResponse
    {
        $document = $indexer->index(
            $request->string('title')->toString(),
            $request->string('content')->toString(),
        );

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }
}

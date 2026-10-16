<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchDocumentsRequest;
use App\Http\Resources\SearchResultResource;
use App\Services\HybridSearchService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        SearchDocumentsRequest $request,
        HybridSearchService $search,
    ): AnonymousResourceCollection {
        $limit = $request->integer('limit', 10);
        $results = $search->search($request->string('q')->toString(), $limit);

        return SearchResultResource::collection($results)->additional([
            'meta' => [
                'query' => $request->string('q')->toString(),
                'limit' => $limit,
            ],
        ]);
    }
}

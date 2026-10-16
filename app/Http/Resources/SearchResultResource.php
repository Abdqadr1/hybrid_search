<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['document']->getKey(),
            'title' => $this->resource['document']->title,
            'content' => $this->resource['document']->content,
            'score' => $this->resource['rrf_score'],
            'ranks' => [
                'keyword' => $this->resource['keyword_rank'],
                'semantic' => $this->resource['semantic_rank'],
            ],
        ];
    }
}

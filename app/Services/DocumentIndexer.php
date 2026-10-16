<?php

namespace App\Services;

use App\Contracts\EmbeddingGenerator;
use App\Models\Document;
use App\Repositories\DocumentRepository;

class DocumentIndexer
{
    public function __construct(
        private EmbeddingGenerator $embeddingGenerator,
        private DocumentRepository $documents,
    ) {}

    public function index(string $title, string $content): Document
    {
        $embedding = $this->embeddingGenerator->generate($title."\n\n".$content);

        return $this->documents->create($title, $content, $embedding);
    }
}

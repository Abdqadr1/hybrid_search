<?php

namespace Database\Seeders;

use App\Services\DocumentIndexer;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(DocumentIndexer $indexer): void
    {
        $documents = [
            [
                'title' => 'Laravel queues',
                'content' => 'Queues move slow work out of an HTTP request and into a background worker.',
            ],
            [
                'title' => 'PostgreSQL full-text search',
                'content' => 'PostgreSQL can rank documents against parsed search queries with tsvector and tsquery.',
            ],
            [
                'title' => 'Vector similarity search',
                'content' => 'pgvector compares embeddings with distance operators, including cosine distance.',
            ],
        ];

        foreach ($documents as $document) {
            $indexer->index($document['title'], $document['content']);
        }
    }
}

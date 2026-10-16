<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');

            if (DB::connection()->getDriverName() !== 'pgsql') {
                $table->text('search_vector')->nullable();
                $table->text('embedding')->nullable();
            }

            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE documents
                ADD COLUMN search_vector tsvector
                GENERATED ALWAYS AS (
                    setweight(to_tsvector('english', coalesce(title, '')), 'A') ||
                    setweight(to_tsvector('english', coalesce(content, '')), 'B')
                ) STORED
            SQL);
            DB::statement('ALTER TABLE documents ADD COLUMN embedding vector(512) NOT NULL');
            DB::statement('CREATE INDEX documents_search_vector_index ON documents USING GIN (search_vector)');
            DB::statement('CREATE INDEX documents_embedding_hnsw_index ON documents USING hnsw (embedding vector_cosine_ops)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncr_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ncr_id')->unique()->constrained()->cascadeOnDelete();
            $table->vector('embedding', dimensions: (int) env('EMBEDDING_DIMENSIONS', 768));
            $table->timestamps();
        });

        DB::statement(
            'CREATE INDEX ncr_embeddings_embedding_idx ON ncr_embeddings
             USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ncr_embeddings');
    }
};

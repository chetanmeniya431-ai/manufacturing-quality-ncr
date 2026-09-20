<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('quality_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('chunk_text');
            $table->unsignedInteger('page_estimate')->nullable();
            $table->vector('embedding', dimensions: (int) env('EMBEDDING_DIMENSIONS', 768));
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement(
            'CREATE INDEX document_chunks_embedding_idx ON document_chunks
             USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};

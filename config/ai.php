<?php

return [
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://host.docker.internal:11434'),
        'generation_model' => env('OLLAMA_GENERATION_MODEL', 'llama3.2:3b'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 60),
    ],

    'embedding_dimensions' => (int) env('EMBEDDING_DIMENSIONS', 768),

    'chunk_size' => (int) env('CHUNK_SIZE', 500),
    'chunk_overlap' => (int) env('CHUNK_OVERLAP', 50),
    'similarity_threshold' => (float) env('SIMILARITY_THRESHOLD', 0.5),
    'top_k_chunks' => (int) env('TOP_K_CHUNKS', 5),

    'embedding_batch_size' => (int) env('EMBEDDING_BATCH_SIZE', 10),

    'max_file_size_kb' => 20 * 1024,
];

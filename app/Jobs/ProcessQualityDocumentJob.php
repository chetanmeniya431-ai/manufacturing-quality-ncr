<?php

namespace App\Jobs;

use App\Models\DocumentChunk;
use App\Models\QualityDocument;
use App\Services\Documents\Chunker;
use App\Services\Documents\TextExtractor;
use App\Services\Ollama\OllamaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Vector;
use Throwable;

class ProcessQualityDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(public int $documentId)
    {
    }

    public function handle(TextExtractor $extractor, Chunker $chunker, OllamaClient $ollama): void
    {
        $document = QualityDocument::find($this->documentId);

        if (! $document) {
            return;
        }

        $document->update(['status' => 'processing', 'error_message' => null]);

        try {
            $absolutePath = Storage::disk('local')->path($document->file_path);
            $pages = $extractor->extractPages($absolutePath);
            $chunks = $chunker->chunkPages($pages);

            if (empty($chunks)) {
                throw new \RuntimeException('No extractable text was found in this document.');
            }

            $batchSize = (int) config('ai.embedding_batch_size', 10);
            $stored = 0;

            DB::transaction(function () use ($document, $chunks, $ollama, $batchSize, &$stored) {
                DocumentChunk::where('document_id', $document->id)->delete();

                foreach (array_chunk($chunks, $batchSize, true) as $batch) {
                    $texts = array_column($batch, 'text');
                    $vectors = $ollama->embedBatch($texts);

                    $rows = [];
                    $i = 0;
                    foreach ($batch as $index => $chunk) {
                        $rows[] = [
                            'document_id' => $document->id,
                            'chunk_index' => $index,
                            'chunk_text' => $chunk['text'],
                            'page_estimate' => $chunk['page'],
                            'embedding' => (string) new Vector($vectors[$i]),
                            'created_at' => now(),
                        ];
                        $i++;
                    }

                    DocumentChunk::insert($rows);
                    $stored += count($rows);
                }
            });

            $document->update([
                'status' => 'ready',
                'chunk_count' => $stored,
                'embedded_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        QualityDocument::where('id', $this->documentId)->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\ProcessQualityDocumentJob;
use App\Models\Ncr;
use App\Models\QualityDocument;
use App\Services\Ncr\NcrSimilarityService;
use Illuminate\Console\Command;

class ProcessPendingEmbeddings extends Command
{
    protected $signature = 'embeddings:process-pending';

    protected $description = 'Dispatch embedding work for quality documents and NCRs that do not have embeddings yet.';

    public function handle(NcrSimilarityService $similarity): int
    {
        $batchSize = (int) config('ai.embedding_batch_size', 10);

        $pendingDocuments = QualityDocument::where('status', 'pending')->limit($batchSize)->get();

        foreach ($pendingDocuments as $document) {
            ProcessQualityDocumentJob::dispatch($document->id);
        }

        $ncrsWithoutEmbeddings = Ncr::doesntHave('embedding')->limit($batchSize)->get();

        foreach ($ncrsWithoutEmbeddings as $ncr) {
            $similarity->embed($ncr);
        }

        $this->info(sprintf(
            'Queued %d document(s) for processing, embedded %d NCR(s).',
            $pendingDocuments->count(),
            $ncrsWithoutEmbeddings->count(),
        ));

        return self::SUCCESS;
    }
}

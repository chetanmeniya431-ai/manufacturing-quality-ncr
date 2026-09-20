<?php

namespace App\Jobs;

use App\Models\Ncr;
use App\Services\Ncr\NcrSimilarityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateNcrAiSuggestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $ncrId)
    {
    }

    /**
     * Space retries out instead of hammering an already-overloaded Ollama
     * instance back to back.
     */
    public function backoff(): array
    {
        return [30, 60];
    }

    public function handle(NcrSimilarityService $similarity): void
    {
        $ncr = Ncr::find($this->ncrId);

        if (! $ncr) {
            return;
        }

        $similar = $similarity->findSimilarClosed($ncr);
        $suggestion = $similarity->suggestRootCause($ncr, $similar);

        $ncr->update([
            'ai_similar_ncrs' => $similar->map(fn ($row) => [
                'ncr_id' => $row['ncr']->id,
                'similarity' => $row['similarity'],
            ])->all(),
            'ai_root_cause_suggestion' => $suggestion,
            'ai_suggestion_status' => 'ready',
        ]);
    }

    public function failed(Throwable $e): void
    {
        report($e);

        Ncr::where('id', $this->ncrId)->update(['ai_suggestion_status' => 'failed']);
    }
}

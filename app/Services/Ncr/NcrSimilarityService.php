<?php

namespace App\Services\Ncr;

use App\Models\Ncr;
use App\Models\NcrEmbedding;
use App\Services\Ollama\OllamaClient;
use Pgvector\Laravel\Distance;

class NcrSimilarityService
{
    public function __construct(protected OllamaClient $ollama)
    {
    }

    /**
     * Embed the NCR's description and store/refresh its embedding row.
     */
    public function embed(Ncr $ncr): NcrEmbedding
    {
        $vector = $this->ollama->embed($ncr->description);

        return NcrEmbedding::updateOrCreate(
            ['ncr_id' => $ncr->id],
            ['embedding' => $vector],
        );
    }

    /**
     * Find the top-3 most similar *closed* past NCRs by cosine distance on
     * the description embedding, using pgvector's nearest-neighbor index.
     */
    public function findSimilarClosed(Ncr $ncr, int $limit = 3): \Illuminate\Support\Collection
    {
        $vector = $this->ollama->embed($ncr->description);

        $neighborIds = NcrEmbedding::query()
            ->whereHas('ncr', fn ($q) => $q->where('status', 'closed')->where('id', '!=', $ncr->id))
            ->nearestNeighbors('embedding', $vector, Distance::Cosine)
            ->limit($limit)
            ->get();

        return $neighborIds->map(function (NcrEmbedding $embedding) {
            $embedding->loadMissing('ncr');

            return [
                'ncr' => $embedding->ncr,
                'distance' => $embedding->neighbor_distance,
                'similarity' => round(1 - $embedding->neighbor_distance, 3),
            ];
        })->filter(fn ($row) => $row['ncr'] !== null)->values();
    }

    /**
     * Generate the AI root-cause suggestion from similar past NCRs. Does not
     * set any field on the NCR itself — the caller decides what to do with
     * the text (it's shown as a starting point only).
     */
    public function suggestRootCause(Ncr $ncr, \Illuminate\Support\Collection $similarCases): string
    {
        if ($similarCases->isEmpty()) {
            return 'No similar past NCRs were found, so no AI suggestion is available yet. Record the root cause based on your own investigation.';
        }

        $context = $similarCases->map(function ($row, $i) {
            $n = $i + 1;
            $ncr = $row['ncr'];

            return "Similar case {$n} (similarity {$row['similarity']}):\n".
                "Description: {$ncr->description}\n".
                'Root cause: '.($ncr->root_cause ?: 'not recorded')."\n".
                'Corrective action: '.($ncr->corrective_action ?: 'not recorded');
        })->implode("\n\n");

        $prompt = <<<PROMPT
        A new Non-Conformance Report (NCR) was just logged:
        "{$ncr->description}"

        Here are the {$similarCases->count()} most similar past NCRs, already resolved:

        {$context}

        Based on these similar past NCRs, suggest a likely root cause for the
        new NCR and the corrective action that worked before. Answer in the
        format:
        "Based on {$similarCases->count()} similar past NCRs, the likely root
        cause is [X]. The corrective action that worked before was [Y]."
        Keep it concise — 2-3 sentences.
        PROMPT;

        return $this->ollama->chat([
            ['role' => 'system', 'content' => 'You are a quality engineering assistant helping investigate manufacturing non-conformances. Only use the information given.'],
            ['role' => 'user', 'content' => $prompt],
        ]);
    }
}

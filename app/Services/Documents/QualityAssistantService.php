<?php

namespace App\Services\Documents;

use App\Models\DocumentChunk;
use App\Services\Ollama\OllamaClient;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class QualityAssistantService
{
    public function __construct(protected OllamaClient $ollama)
    {
    }

    /**
     * Answer a staff question using retrieval-augmented generation over the
     * uploaded quality documents. Returns the answer text plus the source
     * chunks used, so the caller can show "document name, page estimate".
     *
     * @return array{answer: string, sources: Collection<int, DocumentChunk>}
     */
    public function ask(string $question): array
    {
        $questionVector = $this->ollama->embed($question);
        $matches = $this->retrieve($questionVector);

        if ($matches->isEmpty()) {
            return [
                'answer' => "I couldn't find anything relevant in the uploaded quality documents to answer that. Try rephrasing, or upload the document that covers this topic.",
                'sources' => collect(),
            ];
        }

        $answer = $this->generate($question, $matches);

        return ['answer' => $answer, 'sources' => $matches];
    }

    protected function retrieve(array $questionVector): Collection
    {
        $threshold = (float) config('ai.similarity_threshold');
        $maxDistance = 1 - $threshold;
        $vector = new Vector($questionVector);

        return DocumentChunk::query()
            ->whereHas('document', fn ($q) => $q->where('status', 'ready'))
            ->nearestNeighbors('embedding', $questionVector, Distance::Cosine)
            ->whereRaw('(embedding <=> ?) <= ?', [$vector, $maxDistance])
            ->limit((int) config('ai.top_k_chunks'))
            ->get();
    }

    protected function generate(string $question, Collection $matches): string
    {
        $context = $matches->map(function (DocumentChunk $chunk, int $i) {
            $doc = $chunk->document;
            $n = $i + 1;
            $page = $chunk->page_estimate ? ", page ~{$chunk->page_estimate}" : '';

            return "[Source {$n}: {$doc?->name}{$page}]\n{$chunk->chunk_text}";
        })->implode("\n\n---\n\n");

        $system = <<<PROMPT
        You are the Quality Assistant for an ISO 9001 certified manufacturer.
        Answer the staff member's question using ONLY the quality document
        excerpts given below. You may combine and connect information from
        different excerpts to answer, as long as every fact you state is
        actually present in them — never invent or assume a fact that isn't
        there. If the excerpts genuinely don't cover the question, respond
        warmly and helpfully, e.g. "I couldn't find that in the uploaded
        documents — it may be worth checking [closest related topic], or
        uploading the document that covers this." Never reply with just "I
        don't know." Cite which source number(s) you used. Keep answers
        concise and practical.
        PROMPT;

        $userPrompt = <<<PROMPT
        Quality document excerpts:
        {$context}

        Question: {$question}
        PROMPT;

        return $this->ollama->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\AssistantAnswer;
use App\Services\Documents\QualityAssistantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AnswerAssistantQuestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $answerId)
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

    public function handle(QualityAssistantService $service): void
    {
        $answer = AssistantAnswer::find($this->answerId);

        if (! $answer) {
            return;
        }

        $result = $service->ask($answer->question);

        $answer->update([
            'answer' => $result['answer'],
            'sources' => $result['sources']->map(fn ($chunk) => [
                'document' => $chunk->document?->name,
                'page' => $chunk->page_estimate,
            ])->unique('document')->values()->all(),
            'status' => 'completed',
        ]);
    }

    public function failed(Throwable $e): void
    {
        report($e);

        AssistantAnswer::where('id', $this->answerId)->update(['status' => 'failed']);
    }
}

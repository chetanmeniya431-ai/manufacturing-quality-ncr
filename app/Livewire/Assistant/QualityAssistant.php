<?php

namespace App\Livewire\Assistant;

use App\Jobs\AnswerAssistantQuestionJob;
use App\Models\AssistantAnswer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class QualityAssistant extends Component
{
    public string $question = '';

    public function mount(): void
    {
        abort_if(auth()->user()->isSupplier() || auth()->user()->isReadOnly(), 403);
    }

    public function ask(): void
    {
        $this->validate(['question' => ['required', 'string', 'min:3']]);

        $answer = AssistantAnswer::create([
            'user_id' => auth()->id(),
            'question' => $this->question,
            'status' => 'pending',
        ]);

        $this->question = '';

        AnswerAssistantQuestionJob::dispatch($answer->id);
    }

    public function render()
    {
        $conversation = AssistantAnswer::where('user_id', auth()->id())
            ->orderBy('created_at')
            ->get();

        return view('livewire.assistant.index', [
            'conversation' => $conversation,
            'hasPending' => $conversation->contains('status', 'pending'),
        ]);
    }
}

<?php

namespace App\Livewire\Ncr;

use App\Jobs\GenerateNcrAiSuggestionJob;
use App\Models\Ncr;
use App\Models\User;
use App\Services\Ncr\NcrWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NcrShow extends Component
{
    public Ncr $ncr;

    public bool $aiLoaded = false;
    public array $similarCases = [];

    public string $root_cause = '';
    public string $corrective_action = '';
    public ?int $corrective_action_assigned_to = null;
    public string $corrective_action_due = '';
    public string $verification_notes = '';
    public string $closure_notes = '';
    public bool $verification_confirmed = false;
    public string $supplier_response = '';

    public function mount(Ncr $ncr): void
    {
        $user = auth()->user();

        if ($user->isSupplier() && $ncr->supplier_id !== $user->supplier_id) {
            abort(403);
        }

        $this->ncr = $ncr;
        $this->root_cause = (string) $ncr->root_cause;
        $this->corrective_action = (string) $ncr->corrective_action;
        $this->corrective_action_assigned_to = $ncr->corrective_action_assigned_to;
        $this->corrective_action_due = optional($ncr->corrective_action_due)->toDateString() ?? '';
        $this->verification_notes = (string) $ncr->verification_notes;
        $this->aiLoaded = $ncr->ai_suggestion_status === 'ready';

        if ($this->aiLoaded) {
            $this->similarCases = collect($ncr->ai_similar_ncrs)
                ->map(fn ($row) => [
                    'ncr' => Ncr::find($row['ncr_id']),
                    'similarity' => $row['similarity'],
                ])
                ->filter(fn ($row) => $row['ncr'] !== null)
                ->values()
                ->all();
        }
    }

    public function loadAiSuggestions(): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);

        $this->ncr->update(['ai_suggestion_status' => 'pending']);
        GenerateNcrAiSuggestionJob::dispatch($this->ncr->id);
    }

    public function checkAiSuggestion(): void
    {
        if ($this->ncr->ai_suggestion_status !== 'pending') {
            return;
        }

        $this->ncr->refresh();

        if ($this->ncr->ai_suggestion_status === 'ready') {
            $this->similarCases = collect($this->ncr->ai_similar_ncrs)
                ->map(fn ($row) => [
                    'ncr' => Ncr::find($row['ncr_id']),
                    'similarity' => $row['similarity'],
                ])
                ->filter(fn ($row) => $row['ncr'] !== null)
                ->values()
                ->all();
            $this->aiLoaded = true;
        } elseif ($this->ncr->ai_suggestion_status === 'failed') {
            session()->flash('error', 'AI suggestions are temporarily unavailable. Please try again shortly.');
        }
    }

    public function moveToInvestigating(NcrWorkflowService $workflow): void
    {
        $this->authorizeManage();
        $workflow->advance($this->ncr, auth()->user());
        $this->ncr->refresh();
        session()->flash('status', 'NCR moved to Under Investigation.');
    }

    public function assignCorrectiveAction(NcrWorkflowService $workflow): void
    {
        $this->authorizeManage();

        $data = $this->validate([
            'root_cause' => ['required', 'string', 'min:5'],
            'corrective_action' => ['required', 'string', 'min:5'],
            'corrective_action_assigned_to' => ['required', 'exists:users,id'],
            'corrective_action_due' => ['required', 'date', 'after_or_equal:today'],
        ]);

        try {
            $workflow->advance($this->ncr, auth()->user(), $data);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['corrective_action' => $e->getMessage()]);
        }

        $this->ncr->refresh();
        session()->flash('status', 'Corrective action assigned.');
    }

    public function moveToVerification(NcrWorkflowService $workflow): void
    {
        $this->authorizeManage();
        $workflow->advance($this->ncr, auth()->user());
        $this->ncr->refresh();
        session()->flash('status', 'NCR moved to Verification.');
    }

    public function closeNcr(NcrWorkflowService $workflow): void
    {
        abort_unless(auth()->user()->canCloseNcrs(), 403);

        $data = $this->validate([
            'verification_notes' => ['required', 'string', 'min:5'],
            'closure_notes' => ['required', 'string', 'min:5'],
            'verification_confirmed' => ['accepted'],
        ]);

        try {
            $workflow->advance($this->ncr, auth()->user(), $data);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['closure_notes' => $e->getMessage()]);
        }

        $this->ncr->refresh();
        session()->flash('status', "NCR {$this->ncr->ncr_number} closed.");
    }

    public function submitSupplierResponse(): void
    {
        abort_unless(auth()->user()->isSupplier(), 403);

        $this->validate(['supplier_response' => ['required', 'string', 'min:3']]);

        \App\Models\NcrStatusHistory::create([
            'ncr_id' => $this->ncr->id,
            'old_status' => $this->ncr->status,
            'new_status' => $this->ncr->status,
            'changed_by' => auth()->id(),
            'note' => 'Supplier response: '.$this->supplier_response,
        ]);

        $this->supplier_response = '';
        $this->ncr->refresh();
        session()->flash('status', 'Response submitted.');
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);
    }

    public function render()
    {
        return view('livewire.ncr.show', [
            'assignees' => User::role(['quality_manager', 'quality_inspector', 'production_manager'])->orderBy('name')->get(),
        ]);
    }
}

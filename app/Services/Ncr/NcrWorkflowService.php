<?php

namespace App\Services\Ncr;

use App\Models\Ncr;
use App\Models\NcrStatusHistory;
use App\Models\User;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

class NcrWorkflowService
{
    /**
     * Move the NCR forward exactly one step in the fixed linear workflow.
     * Status can never move backward or skip a step.
     *
     * @param  array{closure_notes?: string, verification_confirmed?: bool, corrective_action_assigned_to?: int, corrective_action_due?: string, root_cause?: string, corrective_action?: string, verification_notes?: string}  $data
     */
    public function advance(Ncr $ncr, User $user, array $data = []): Ncr
    {
        $nextStatus = $ncr->nextStatus();

        if ($nextStatus === null) {
            throw new InvalidArgumentException('This NCR is already closed.');
        }

        if ($nextStatus === 'closed') {
            $this->assertCanClose($data);
        }

        return DB::transaction(function () use ($ncr, $user, $nextStatus, $data) {
            $oldStatus = $ncr->status;
            $ncr->status = $nextStatus;

            match ($nextStatus) {
                'investigating' => $ncr->investigation_started_at = now(),
                'corrective_assigned' => $this->applyCorrectiveAssignment($ncr, $data),
                'verification' => $ncr->verification_started_at = now(),
                'closed' => $this->applyClosure($ncr, $user, $data),
                default => null,
            };

            if (isset($data['root_cause'])) {
                $ncr->root_cause = $data['root_cause'];
            }

            $ncr->save();

            NcrStatusHistory::create([
                'ncr_id' => $ncr->id,
                'old_status' => $oldStatus,
                'new_status' => $nextStatus,
                'changed_by' => $user->id,
                'note' => $data['note'] ?? null,
            ]);

            return $ncr;
        });
    }

    protected function applyCorrectiveAssignment(Ncr $ncr, array $data): void
    {
        $ncr->corrective_action_assigned_at = now();
        $ncr->corrective_action = $data['corrective_action'] ?? $ncr->corrective_action;
        $ncr->corrective_action_assigned_to = $data['corrective_action_assigned_to'] ?? $ncr->corrective_action_assigned_to;
        $ncr->corrective_action_due = $data['corrective_action_due'] ?? $ncr->corrective_action_due;
    }

    protected function applyClosure(Ncr $ncr, User $user, array $data): void
    {
        $ncr->verification_notes = $data['verification_notes'] ?? $ncr->verification_notes;
        $ncr->closure_notes = $data['closure_notes'];
        $ncr->closed_by = $user->id;
        $ncr->closed_at = now();
    }

    protected function assertCanClose(array $data): void
    {
        if (empty($data['closure_notes'])) {
            throw new InvalidArgumentException('A closure note is required to close an NCR.');
        }

        if (empty($data['verification_confirmed'])) {
            throw new InvalidArgumentException('You must confirm the corrective action was verified effective before closing.');
        }
    }
}

<?php

namespace App\Livewire\Signals;

use App\Models\Signal;
use App\Models\SignalEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SignalsDashboard extends Component
{
    public bool $showResolved = false;

    public ?int $editingSignalId = null;

    /** @var array<string, int|null> */
    public array $editValues = [];

    public function mount(): void
    {
        abort_if(auth()->user()->isSupplier(), 403);
    }

    public function resolve(int $eventId): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);

        $event = SignalEvent::findOrFail($eventId);
        $event->update([
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);
    }

    public function toggleSignal(int $signalId): void
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isQualityManager(), 403);

        $signal = Signal::findOrFail($signalId);
        $signal->update(['active' => ! $signal->active]);
    }

    public function editSignal(int $signalId): void
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isQualityManager(), 403);

        $signal = Signal::findOrFail($signalId);
        $this->editingSignalId = $signalId;
        $this->editValues = [
            'threshold' => $signal->threshold,
            'window_days' => $signal->window_days,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingSignalId = null;
        $this->editValues = [];
    }

    public function saveSignal(int $signalId): void
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isQualityManager(), 403);

        $signal = Signal::findOrFail($signalId);
        $fields = $signal->configurableFields();

        if (empty($fields)) {
            $this->editingSignalId = null;

            return;
        }

        $rules = [];
        foreach ($fields as $field => $config) {
            $rules["editValues.{$field}"] = ['required', 'integer', 'min:'.$config['min'], 'max:'.$config['max']];
        }
        $validated = $this->validate($rules);

        $signal->update(array_intersect_key($validated['editValues'], $fields));

        $this->editingSignalId = null;
        $this->editValues = [];
        session()->flash('status', "\"{$signal->name}\" updated.");
    }

    public function render()
    {
        $events = SignalEvent::with(['signal', 'ncr', 'supplier'])
            ->when(! $this->showResolved, fn ($q) => $q->whereNull('resolved_at'))
            ->orderByDesc('triggered_at')
            ->limit(100)
            ->get();

        return view('livewire.signals.index', [
            'events' => $events,
            'signals' => Signal::orderBy('name')->get(),
        ]);
    }
}

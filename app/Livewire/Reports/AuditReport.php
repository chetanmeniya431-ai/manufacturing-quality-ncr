<?php

namespace App\Livewire\Reports;

use App\Models\Ncr;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AuditReport extends Component
{
    public string $period = '90';
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        abort_if(auth()->user()->isSupplier(), 403);
        $this->from = now()->subDays(90)->toDateString();
        $this->to = now()->toDateString();
    }

    public function getDownloadUrlProperty(): string
    {
        $params = ['period' => $this->period];

        if ($this->period === 'custom') {
            $params['from'] = $this->from;
            $params['to'] = $this->to;
        }

        return route('reports.download', $params);
    }

    public function render()
    {
        $days = $this->period === 'custom' ? null : (int) $this->period;
        $from = $days ? now()->subDays($days) : \Illuminate\Support\Carbon::parse($this->from);
        $to = $days ? now() : \Illuminate\Support\Carbon::parse($this->to);

        return view('livewire.reports.index', [
            'previewCount' => Ncr::whereBetween('detected_date', [$from->toDateString(), $to->toDateString()])->count(),
        ]);
    }
}

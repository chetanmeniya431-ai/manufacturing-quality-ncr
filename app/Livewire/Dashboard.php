<?php

namespace App\Livewire;

use App\Models\Ncr;
use App\Models\SignalEvent;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        if ($user->isSupplier()) {
            $ncrs = Ncr::where('supplier_id', $user->supplier_id)
                ->latest('detected_date')
                ->limit(10)
                ->get();

            return view('livewire.dashboard.supplier', [
                'ncrs' => $ncrs,
                'supplier' => $user->supplier,
            ]);
        }

        $counts = [
            'open' => Ncr::where('status', 'open')->count(),
            'investigating' => Ncr::where('status', 'investigating')->count(),
            'corrective_assigned' => Ncr::where('status', 'corrective_assigned')->count(),
            'verification' => Ncr::where('status', 'verification')->count(),
            'closed' => Ncr::where('status', 'closed')->count(),
        ];

        $bySeverity = [
            'critical' => Ncr::where('severity', 'critical')->where('status', '!=', 'closed')->count(),
            'major' => Ncr::where('severity', 'major')->where('status', '!=', 'closed')->count(),
            'minor' => Ncr::where('severity', 'minor')->where('status', '!=', 'closed')->count(),
        ];

        $openSignalEvents = SignalEvent::with(['signal', 'ncr', 'supplier'])
            ->whereNull('resolved_at')
            ->orderByDesc('triggered_at')
            ->limit(8)
            ->get();

        $suppliers = Supplier::all()->sortBy(fn (Supplier $s) => $s->qualityScore());

        $recentNcrs = Ncr::with(['supplier', 'detectedBy'])
            ->latest('detected_date')
            ->limit(6)
            ->get();

        return view('livewire.dashboard.index', [
            'counts' => $counts,
            'bySeverity' => $bySeverity,
            'openSignalEvents' => $openSignalEvents,
            'suppliers' => $suppliers,
            'recentNcrs' => $recentNcrs,
        ]);
    }
}

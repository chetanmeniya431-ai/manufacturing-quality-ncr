<?php

namespace App\Services\Reports;

use App\Models\Ncr;
use App\Models\QualityDocument;
use App\Models\SignalEvent;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class AuditReportService
{
    public function build(Carbon $from, Carbon $to): \Barryvdh\DomPDF\PDF
    {
        $ncrs = Ncr::with(['supplier', 'detectedBy', 'correctiveActionAssignee'])
            ->whereBetween('detected_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('detected_date')
            ->get();

        $byStatus = $ncrs->groupBy('status')->map->count();
        $bySeverity = $ncrs->groupBy('severity')->map->count();
        $byCategory = $ncrs->groupBy('defect_category')->map->count();

        $correctiveActions = $ncrs->filter(fn (Ncr $n) => filled($n->corrective_action));

        $suppliers = Supplier::orderBy('name')->get();

        $openSignalEvents = SignalEvent::with(['signal', 'ncr', 'supplier'])
            ->whereNull('resolved_at')
            ->orderByDesc('triggered_at')
            ->get();

        $documents = QualityDocument::orderBy('name')->get();

        return Pdf::loadView('reports.audit', [
            'from' => $from,
            'to' => $to,
            'ncrs' => $ncrs,
            'byStatus' => $byStatus,
            'bySeverity' => $bySeverity,
            'byCategory' => $byCategory,
            'correctiveActions' => $correctiveActions,
            'suppliers' => $suppliers,
            'openSignalEvents' => $openSignalEvents,
            'documents' => $documents,
            'generatedAt' => now(),
        ])->setPaper('a4');
    }
}

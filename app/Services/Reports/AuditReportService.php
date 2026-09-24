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
    // DomPDF's per-row memory overhead is much higher than the raw HTML size —
    // 500+300 rows still exhausted a 512M limit with no catchable error (a true
    // "memory exhausted" fatal bypasses try/catch entirely). Verified locally by
    // seeding 3,000+ NCRs / 600+ signal events to match production's actual
    // volume: these caps, plus the memory_limit bump below, complete reliably at
    // that scale. Each section shows a "+N more" note when truncated.
    private const MAX_NCRS = 200;
    private const MAX_SIGNAL_EVENTS = 100;
    private const MAX_DOCUMENTS = 100;

    public function build(Carbon $from, Carbon $to): \Barryvdh\DomPDF\PDF
    {
        // One-off, explicitly user-triggered action — safe to raise the ceiling
        // just for this call rather than for every request.
        ini_set('memory_limit', '1024M');

        $ncrsQuery = Ncr::whereBetween('detected_date', [$from->toDateString(), $to->toDateString()]);

        // Summary counts come from the full (uncapped) filtered set via GROUP BY
        // aggregates — cheap regardless of volume, and stay accurate even when
        // the detailed list below is truncated.
        $byStatus = (clone $ncrsQuery)->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $bySeverity = (clone $ncrsQuery)->selectRaw('severity, count(*) as aggregate')->groupBy('severity')->pluck('aggregate', 'severity');
        $byCategory = (clone $ncrsQuery)->selectRaw('defect_category, count(*) as aggregate')->groupBy('defect_category')->pluck('aggregate', 'defect_category');

        $ncrTotal = $ncrsQuery->count();
        $ncrs = (clone $ncrsQuery)
            ->with(['supplier', 'detectedBy', 'correctiveActionAssignee'])
            ->orderBy('detected_date')
            ->limit(self::MAX_NCRS)
            ->get();

        $correctiveActions = $ncrs->filter(fn (Ncr $n) => filled($n->corrective_action));

        $suppliers = Supplier::orderBy('name')->get();

        $signalEventTotal = SignalEvent::whereNull('resolved_at')->count();
        $openSignalEvents = SignalEvent::with(['signal', 'ncr', 'supplier'])
            ->whereNull('resolved_at')
            ->orderByDesc('triggered_at')
            ->limit(self::MAX_SIGNAL_EVENTS)
            ->get();

        $documentTotal = QualityDocument::count();
        $documents = QualityDocument::orderBy('name')->limit(self::MAX_DOCUMENTS)->get();

        return Pdf::loadView('reports.audit', [
            'from' => $from,
            'to' => $to,
            'ncrs' => $ncrs,
            'ncrTotal' => $ncrTotal,
            'byStatus' => $byStatus,
            'bySeverity' => $bySeverity,
            'byCategory' => $byCategory,
            'correctiveActions' => $correctiveActions,
            'suppliers' => $suppliers,
            'openSignalEvents' => $openSignalEvents,
            'signalEventTotal' => $signalEventTotal,
            'documents' => $documents,
            'documentTotal' => $documentTotal,
            'generatedAt' => now(),
        ])->setPaper('a4');
    }
}

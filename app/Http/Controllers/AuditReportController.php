<?php

namespace App\Http\Controllers;

use App\Services\Reports\AuditReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditReportController extends Controller
{
    public function __invoke(Request $request, AuditReportService $service)
    {
        abort_if(auth()->user()->isSupplier(), 403);

        $period = $request->query('period', '90');

        if ($period === 'custom') {
            $from = Carbon::parse($request->query('from', now()->subDays(90)))->startOfDay();
            $to = Carbon::parse($request->query('to', now()))->endOfDay();
        } else {
            $days = (int) $period;
            $from = now()->subDays($days)->startOfDay();
            $to = now()->endOfDay();
        }

        try {
            $pdf = $service->build($from, $to);
        } catch (Throwable $e) {
            // The dompdf render can fail for reasons that don't show up anywhere
            // else (large/odd data, memory) — log it explicitly so a failure is
            // never silent, then show a page instead of a raw 500.
            Log::error('Audit report generation failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'from'    => $from->toDateString(),
                'to'      => $to->toDateString(),
            ]);

            return back()->with('error', 'The report could not be generated. This has been logged — please try a shorter date range or contact support.');
        }

        return $pdf->download('audit-report-'.now()->format('Y-m-d').'.pdf');
    }
}

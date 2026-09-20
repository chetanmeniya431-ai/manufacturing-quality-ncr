<?php

namespace App\Http\Controllers;

use App\Services\Reports\AuditReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $pdf = $service->build($from, $to);

        return $pdf->download('audit-report-'.now()->format('Y-m-d').'.pdf');
    }
}

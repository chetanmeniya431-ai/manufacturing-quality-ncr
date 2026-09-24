<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; }
    h1 { font-size: 18px; margin-bottom: 2px; }
    h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
    p.sub { color: #6b7280; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; vertical-align: top; }
    th { background: #f9fafb; text-transform: uppercase; font-size: 8px; color: #6b7280; }
    .summary-grid { width: 100%; }
    .summary-grid td { border: none; padding: 2px 6px; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; }
    .badge-critical, .badge-red { background: #fee2e2; color: #b91c1c; }
    .badge-major, .badge-orange { background: #ffedd5; color: #c2410c; }
    .badge-minor, .badge-yellow { background: #fef9c3; color: #a16207; }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-gray { background: #f3f4f6; color: #374151; }
    .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
    <h1>Quality Audit Report</h1>
    <p class="sub">Vantage Precision Parts Ltd — ISO 9001:2015 — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</p>

    <h2>Summary — NCRs by Status</h2>
    <table>
        <tr>
            @foreach(\App\Models\Ncr::STATUS_LABELS as $key => $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach(\App\Models\Ncr::STATUS_LABELS as $key => $label)
                <td>{{ $byStatus[$key] ?? 0 }}</td>
            @endforeach
        </tr>
    </table>

    <h2>Summary — NCRs by Severity</h2>
    <table>
        <tr>
            @foreach(\App\Models\Ncr::SEVERITY_LABELS as $key => $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach(\App\Models\Ncr::SEVERITY_LABELS as $key => $label)
                <td>{{ $bySeverity[$key] ?? 0 }}</td>
            @endforeach
        </tr>
    </table>

    <h2>Summary — NCRs by Defect Category</h2>
    <table>
        <tr>
            @foreach(\App\Models\Ncr::DEFECT_CATEGORY_LABELS as $key => $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach(\App\Models\Ncr::DEFECT_CATEGORY_LABELS as $key => $label)
                <td>{{ $byCategory[$key] ?? 0 }}</td>
            @endforeach
        </tr>
    </table>

    <h2>NCR List ({{ $ncrs->count() }})</h2>
    <table>
        <tr>
            <th>NCR #</th><th>Product</th><th>Category</th><th>Severity</th><th>Status</th>
            <th>Detected</th><th>Supplier</th><th>Detected By</th>
        </tr>
        @foreach($ncrs as $ncr)
        <tr>
            <td>{{ $ncr->ncr_number }}</td>
            <td>{{ $ncr->product_name }}</td>
            <td>{{ \App\Models\Ncr::DEFECT_CATEGORY_LABELS[$ncr->defect_category] }}</td>
            <td>{{ \App\Models\Ncr::SEVERITY_LABELS[$ncr->severity] }}</td>
            <td>{{ \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</td>
            <td>{{ $ncr->detected_date->format('d M Y') }}</td>
            <td>{{ $ncr->supplier?->name ?? '—' }}</td>
            <td>{{ $ncr->detectedBy?->name }}</td>
        </tr>
        @endforeach
    </table>

    <h2>Corrective Actions ({{ $correctiveActions->count() }})</h2>
    <table>
        <tr><th>NCR #</th><th>Corrective Action</th><th>Assigned To</th><th>Due</th><th>Status</th></tr>
        @foreach($correctiveActions as $ncr)
        <tr>
            <td>{{ $ncr->ncr_number }}</td>
            <td>{{ $ncr->corrective_action }}</td>
            <td>{{ $ncr->correctiveActionAssignee?->name ?? '—' }}</td>
            <td>{{ optional($ncr->corrective_action_due)->format('d M Y') ?? '—' }}</td>
            <td>{{ $ncr->status === 'closed' ? 'Completed & verified' : \App\Models\Ncr::STATUS_LABELS[$ncr->status] }}</td>
        </tr>
        @endforeach
    </table>

    <h2>Supplier Quality Scores</h2>
    <table>
        <tr><th>Supplier</th><th>Score</th><th>Approved</th></tr>
        @foreach($suppliers as $supplier)
        <tr>
            <td>{{ $supplier->name }}</td>
            <td>{{ $supplier->qualityScore() }}</td>
            <td>{{ $supplier->approved ? 'Yes' : 'No' }}</td>
        </tr>
        @endforeach
    </table>

    <h2>Open Signal Events ({{ $openSignalEvents->count() }})</h2>
    <table>
        <tr><th>Signal</th><th>Severity</th><th>Related to</th><th>Triggered</th></tr>
        @foreach($openSignalEvents as $event)
        <tr>
            <td>{{ $event->signal->name ?? '—' }}</td>
            <td>{{ $event->signal?->severity ? ucfirst($event->signal->severity) : '—' }}</td>
            <td>{{ $event->ncr?->ncr_number ?? $event->supplier?->name ?? ($event->context ? json_encode($event->context) : '—') }}</td>
            <td>{{ $event->triggered_at->format('d M Y') }}</td>
        </tr>
        @endforeach
    </table>

    <h2>Quality Documents on File</h2>
    <table>
        <tr><th>Name</th><th>Type</th><th>Status</th><th>Uploaded</th></tr>
        @foreach($documents as $document)
        <tr>
            <td>{{ $document->name }}</td>
            <td>{{ \App\Models\QualityDocument::TYPE_LABELS[$document->document_type] }}</td>
            <td>{{ ucfirst($document->status) }}</td>
            <td>{{ $document->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
    </table>

    <p class="footer">Generated {{ $generatedAt->format('d M Y H:i') }} by Quality NCR Manager.</p>
</body>
</html>

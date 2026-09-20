<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class NcrTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $headers = [
            'product_name',
            'defect_category',
            'description',
            'severity',
            'detected_at',
            'detected_date',
            'supplier_name',
        ];

        $example = [
            'Precision Shaft Type A',
            'dimension_out_of_spec',
            'Outer diameter measured 0.15mm over tolerance on batch #4471.',
            'major',
            'final',
            now()->toDateString(),
            'Steelcore Components Ltd',
        ];

        return response()->streamDownload(function () use ($headers, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, $example);
            fclose($handle);
        }, 'ncr-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}

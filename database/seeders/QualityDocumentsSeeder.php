<?php

namespace Database\Seeders;

use App\Jobs\ProcessQualityDocumentJob;
use App\Models\QualityDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class QualityDocumentsSeeder extends Seeder
{
    /**
     * Generates real PDF files from the synthetic quality content (via the
     * app's own dompdf stack) and pushes each one through the exact same
     * ProcessQualityDocumentJob pipeline a manual upload uses — extract,
     * chunk, embed via Ollama, store as pgvector rows. Nothing here is a
     * hand-inserted fixture.
     */
    public function run(): void
    {
        $creator = User::role('quality_manager')->first() ?? User::first();

        $documents = [
            [
                'name' => 'ISO 9001:2015 Quality Manual',
                'type' => 'quality_manual',
                'view' => 'seed-documents.quality-manual',
                'filename' => 'iso-9001-quality-manual.pdf',
            ],
            [
                'name' => 'Product Specification — Precision Shaft Type A',
                'type' => 'product_spec',
                'view' => 'seed-documents.product-spec',
                'filename' => 'product-spec-precision-shaft-a.pdf',
            ],
            [
                'name' => 'Supplier Quality Requirements — Rev 4',
                'type' => 'supplier_req',
                'view' => 'seed-documents.supplier-requirements',
                'filename' => 'supplier-quality-requirements-rev4.pdf',
            ],
        ];

        foreach ($documents as $doc) {
            if (QualityDocument::where('name', $doc['name'])->exists()) {
                continue;
            }

            $pdfBinary = Pdf::loadView($doc['view'])->output();
            $storedPath = 'quality-documents/'.$doc['filename'];
            Storage::disk('local')->put($storedPath, $pdfBinary);

            $document = QualityDocument::create([
                'name' => $doc['name'],
                'file_path' => $storedPath,
                'original_filename' => $doc['filename'],
                'document_type' => $doc['type'],
                'status' => 'pending',
                'created_by' => $creator->id,
            ]);

            ProcessQualityDocumentJob::dispatchSync($document->id);
        }
    }
}

<?php

namespace App\Imports;

use App\Models\Ncr;
use App\Models\Supplier;
use App\Services\Ncr\NcrNumberGenerator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NcrImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    /** @var string[] */
    public array $errors = [];

    public function __construct(protected int $importedBy)
    {
    }

    public function collection(Collection $rows): void
    {
        $numberGenerator = app(NcrNumberGenerator::class);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // account for header row

            $validator = \Illuminate\Support\Facades\Validator::make($row->toArray(), [
                'product_name' => ['required', 'string', 'max:255'],
                'defect_category' => ['required', 'in:'.implode(',', array_keys(Ncr::DEFECT_CATEGORY_LABELS))],
                'description' => ['required', 'string', 'min:10'],
                'severity' => ['required', 'in:minor,major,critical'],
                'detected_at' => ['required', 'in:'.implode(',', array_keys(Ncr::DETECTED_AT_LABELS))],
                'detected_date' => ['required', 'date'],
                'supplier_name' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Row {$rowNumber}: ".implode('; ', $validator->errors()->all());

                continue;
            }

            $data = $validator->validated();

            $supplierId = null;
            if (! empty($data['supplier_name'])) {
                $supplierId = Supplier::where('name', $data['supplier_name'])->value('id');

                if (! $supplierId) {
                    $this->errors[] = "Row {$rowNumber}: supplier \"{$data['supplier_name']}\" not found — NCR created without a supplier link.";
                }
            }

            Ncr::create([
                'ncr_number' => $numberGenerator->next(),
                'product_name' => $data['product_name'],
                'defect_category' => $data['defect_category'],
                'description' => $data['description'],
                'severity' => $data['severity'],
                'detected_at' => $data['detected_at'],
                'detected_date' => $data['detected_date'],
                'supplier_id' => $supplierId,
                'status' => 'open',
                'detected_by' => $this->importedBy,
                'created_by' => $this->importedBy,
            ]);

            $this->created++;
        }
    }
}

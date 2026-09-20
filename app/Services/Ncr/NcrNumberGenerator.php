<?php

namespace App\Services\Ncr;

use App\Models\Ncr;
use Illuminate\Support\Facades\DB;

class NcrNumberGenerator
{
    /**
     * Generate the next NCR-YYYY-NNNN number for the current year, guarding
     * against races with a row lock on the max existing number for the year.
     */
    public function next(): string
    {
        $year = now()->year;
        $prefix = "NCR-{$year}-";

        return DB::transaction(function () use ($year, $prefix) {
            $lastNumber = Ncr::where('ncr_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('ncr_number')
                ->value('ncr_number');

            $nextSeq = 1;

            if ($lastNumber) {
                $nextSeq = (int) substr($lastNumber, -4) + 1;
            }

            return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        });
    }
}

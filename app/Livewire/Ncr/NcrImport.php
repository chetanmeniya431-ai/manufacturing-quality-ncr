<?php

namespace App\Livewire\Ncr;

use App\Imports\NcrImport as NcrImporter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class NcrImport extends Component
{
    use WithFileUploads;

    public $file = null;

    public ?int $created = null;
    public array $errors = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);
    }

    public function import(): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $importer = new NcrImporter(auth()->id());
        Excel::import($importer, $this->file->getRealPath(), null, ExcelFormat::CSV);

        $this->created = $importer->created;
        $this->errors = $importer->errors;
        $this->file = null;
    }

    public function render()
    {
        return view('livewire.ncr.import');
    }
}

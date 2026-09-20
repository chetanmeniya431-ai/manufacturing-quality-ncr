<?php

namespace App\Livewire\Ncr;

use App\Models\Ncr;
use App\Models\Supplier;
use App\Services\Ncr\NcrNumberGenerator;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class NcrCreate extends Component
{
    use WithFileUploads;

    public string $product_name = '';
    public string $defect_category = '';
    public string $description = '';
    public string $severity = '';
    public string $detected_at = '';
    public string $detected_date;
    public ?int $supplier_id = null;

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);
        $this->detected_date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'defect_category' => ['required', 'in:'.implode(',', array_keys(Ncr::DEFECT_CATEGORY_LABELS))],
            'description' => ['required', 'string', 'min:10'],
            'severity' => ['required', 'in:minor,major,critical'],
            'detected_at' => ['required', 'in:'.implode(',', array_keys(Ncr::DETECTED_AT_LABELS))],
            'detected_date' => ['required', 'date', 'before_or_equal:today'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function save(NcrNumberGenerator $numberGenerator)
    {
        $data = $this->validate();

        $paths = collect($this->attachments)->map(function ($file) {
            return $file->store('ncr-attachments', 'local');
        })->values()->all();

        $ncr = Ncr::create([
            ...$data,
            'attachments' => $paths,
            'ncr_number' => $numberGenerator->next(),
            'status' => 'open',
            'detected_by' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        session()->flash('status', "NCR {$ncr->ncr_number} logged successfully.");

        return $this->redirect(route('ncrs.show', $ncr), navigate: false);
    }

    public function render()
    {
        return view('livewire.ncr.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }
}

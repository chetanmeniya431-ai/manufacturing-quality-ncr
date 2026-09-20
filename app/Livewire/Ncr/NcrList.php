<?php

namespace App\Livewire\Ncr;

use App\Models\Ncr;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NcrList extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $severity = '';

    #[Url]
    public string $defect_category = '';

    #[Url]
    public string $supplier_id = '';

    #[Url]
    public string $search = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Ncr::query()->with(['supplier', 'detectedBy'])->latest('detected_date');

        if ($user->isSupplier()) {
            $query->where('supplier_id', $user->supplier_id);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }
        if ($this->severity !== '') {
            $query->where('severity', $this->severity);
        }
        if ($this->defect_category !== '') {
            $query->where('defect_category', $this->defect_category);
        }
        if ($this->supplier_id !== '') {
            $query->where('supplier_id', $this->supplier_id);
        }
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('ncr_number', 'ilike', "%{$this->search}%")
                    ->orWhere('product_name', 'ilike', "%{$this->search}%")
                    ->orWhere('description', 'ilike', "%{$this->search}%");
            });
        }

        return view('livewire.ncr.list', [
            'ncrs' => $query->paginate(15),
            'suppliers' => $user->isSupplier() ? collect() : Supplier::orderBy('name')->get(),
        ]);
    }
}

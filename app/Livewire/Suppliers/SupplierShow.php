<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupplierShow extends Component
{
    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        abort_if(auth()->user()->isSupplier(), 403);
        $this->supplier = $supplier;
    }

    public function toggleApproved(): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);
        $this->supplier->update(['approved' => ! $this->supplier->approved]);
    }

    public function render()
    {
        return view('livewire.suppliers.show', [
            'ncrs' => $this->supplier->ncrs()->latest('detected_date')->limit(20)->get(),
        ]);
    }
}

<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupplierList extends Component
{
    public function mount(): void
    {
        abort_if(auth()->user()->isSupplier(), 403);
    }

    public function render()
    {
        return view('livewire.suppliers.list', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }
}

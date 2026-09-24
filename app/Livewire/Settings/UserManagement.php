<?php

namespace App\Livewire\Settings;

use App\Models\Supplier;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserManagement extends Component
{
    public const ROLES = [
        'quality_manager' => 'Quality Manager',
        'quality_inspector' => 'Quality Inspector',
        'production_manager' => 'Production Manager',
        'supplier' => 'Supplier',
        'auditor' => 'Auditor',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isQualityManager(), 403);
    }

    public function updateRole(int $userId, string $role, ?int $supplierId = null): void
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isQualityManager(), 403);

        if (! array_key_exists($role, self::ROLES)) {
            return;
        }

        $user = User::findOrFail($userId);
        $user->syncRoles([$role]);
        $user->update(['supplier_id' => $role === 'supplier' ? $supplierId : null]);
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::with('roles')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }
}

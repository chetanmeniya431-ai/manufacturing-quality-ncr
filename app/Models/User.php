<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'supplier_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isQualityManager(): bool
    {
        return $this->hasRole('quality_manager');
    }

    public function isQualityInspector(): bool
    {
        return $this->hasRole('quality_inspector');
    }

    public function isProductionManager(): bool
    {
        return $this->hasRole('production_manager');
    }

    public function isSupplier(): bool
    {
        return $this->hasRole('supplier');
    }

    public function isAuditor(): bool
    {
        return $this->hasRole('auditor');
    }

    /**
     * Quality Manager and Quality Inspector can both create/investigate NCRs;
     * only Quality Manager can close them or manage signals/settings.
     */
    public function canManageNcrs(): bool
    {
        return $this->isQualityManager() || $this->isQualityInspector();
    }

    public function canCloseNcrs(): bool
    {
        return $this->isQualityManager();
    }

    public function isReadOnly(): bool
    {
        return $this->isAuditor();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Super Admin',
            'quality_manager',
            'quality_inspector',
            'production_manager',
            'supplier',
            'auditor',
        ] as $role) {
            Role::findOrCreate($role);
        }
    }
}

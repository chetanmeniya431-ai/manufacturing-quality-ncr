<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('nm@2001'), 'email_verified_at' => now()]
        );
        $superAdmin->syncRoles(['Super Admin']);

        $apex = Supplier::where('name', 'Apex Fasteners Pvt Ltd')->first();

        $users = [
            ['name' => 'Asha Mehta', 'email' => 'admin@qualitymanager.local', 'role' => 'quality_manager'],
            ['name' => 'Karan Bhatt', 'email' => 'inspector@qualitymanager.local', 'role' => 'quality_inspector'],
            ['name' => 'Neha Kapoor', 'email' => 'production@qualitymanager.local', 'role' => 'production_manager'],
            ['name' => 'Apex Fasteners Contact', 'email' => 'supplier@qualitymanager.local', 'role' => 'supplier', 'supplier_id' => $apex?->id],
            ['name' => 'Deepak Sharma', 'email' => 'auditor@qualitymanager.local', 'role' => 'auditor'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}

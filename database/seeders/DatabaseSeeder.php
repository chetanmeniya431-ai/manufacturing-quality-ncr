<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(SuppliersSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(SignalsSeeder::class);
        $this->call(QualityDocumentsSeeder::class);
        $this->call(NcrsSeeder::class);
    }
}

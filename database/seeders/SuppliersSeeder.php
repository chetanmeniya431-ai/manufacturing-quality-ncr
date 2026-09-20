<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SuppliersSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Steelcore Components Ltd', 'contact_name' => 'Priya Nair', 'contact_email' => 'priya@steelcore.example', 'product_categories' => ['alloy steel bar stock', 'forgings'], 'approved' => true],
            ['name' => 'Apex Fasteners Pvt Ltd', 'contact_name' => 'Ramesh Iyer', 'contact_email' => 'ramesh@apexfasteners.example', 'product_categories' => ['fasteners', 'hardware'], 'approved' => true],
            ['name' => 'Prima Rubber Seals', 'contact_name' => 'Anita Deshmukh', 'contact_email' => 'anita@primarubber.example', 'product_categories' => ['seals', 'gaskets'], 'approved' => true],
            ['name' => 'Zenith Coatings', 'contact_name' => 'Vikram Shah', 'contact_email' => 'vikram@zenithcoatings.example', 'product_categories' => ['surface coating', 'plating'], 'approved' => true],
            ['name' => 'Metalform Industries', 'contact_name' => 'Sunita Rao', 'contact_email' => 'sunita@metalform.example', 'product_categories' => ['stampings', 'sheet metal'], 'approved' => true],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['name' => $supplier['name']], $supplier);
        }
    }
}

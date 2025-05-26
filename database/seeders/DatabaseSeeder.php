<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create a test product that's originally from our database
        Product::create([
            'ndc_code' => '55700-002',
            'brand_name' => 'Test Aspirin',
            'generic_name' => 'Aspirin',
            'labeler_name' => 'Test Pharma',
            'product_type' => 'HUMAN OTC DRUG',
            'source' => 'Database' // This indicates it's our own database entry, not from OpenFDA
        ]);

        $this->call([
            ProductSeeder::class
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'ndc_code' => '0002-4462',
                'brand_name' => 'Humalog',
                'generic_name' => 'Insulin Lispro',
                'labeler_name' => 'Eli Lilly and Company',
                'product_type' => 'HUMAN PRESCRIPTION DRUG',
                'source' => 'Database'
            ],
            [
                'ndc_code' => '0074-3799',
                'brand_name' => 'Synthroid',
                'generic_name' => 'Levothyroxine Sodium',
                'labeler_name' => 'AbbVie Inc.',
                'product_type' => 'HUMAN PRESCRIPTION DRUG',
                'source' => 'Database'
            ],
            [
                'ndc_code' => '0006-0074',
                'brand_name' => 'Singulair',
                'generic_name' => 'Montelukast Sodium',
                'labeler_name' => 'Merck Sharp & Dohme LLC',
                'product_type' => 'HUMAN PRESCRIPTION DRUG',
                'source' => 'Database'
            ],
            [
                'ndc_code' => '0310-0274',
                'brand_name' => 'Prilosec',
                'generic_name' => 'Omeprazole',
                'labeler_name' => 'AstraZeneca LP',
                'product_type' => 'HUMAN PRESCRIPTION DRUG',
                'source' => 'Database'
            ]
        ];

        // Use DB::table instead of the model to ensure the source is set correctly
        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['ndc_code' => $product['ndc_code']],
                $product
            );
        }
    }
}

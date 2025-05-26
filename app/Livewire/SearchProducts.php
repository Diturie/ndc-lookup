<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class SearchProducts extends Component
{
    public $ndcCodes = '';
    public $results = [];
    public $isSearching = false;
    public $error = '';

    public function search()
    {
        $this->validate([
            'ndcCodes' => 'required|string|min:3'
        ]);

        $this->reset(['results', 'error']);
        $this->isSearching = true;

        $codes = array_map('trim', explode(',', $this->ndcCodes));
        
        foreach ($codes as $code) {
            // Validimi i kodit NDC
            if (!preg_match('/^[0-9A-Za-z]{4,5}-[0-9A-Za-z]{3,4}$/', $code)) {
                $this->results[] = [
                    'ndc_code' => $code,
                    'brand_name' => '-',
                    'labeler_name' => '-',
                    'product_type' => '-',
                    'source' => 'Format i gabuar'
                ];
                continue;
            }
            
            // Kërko në databazën lokale
            $product = Product::where('ndc_code', $code)->first();
            
            if ($product) {
                $this->results[] = [
                    'ndc_code' => $product->ndc_code,
                    'brand_name' => $product->brand_name ?? '-',
                    'labeler_name' => $product->labeler_name ?? '-',
                    'product_type' => $product->product_type ?? '-',
                    'source' => 'Database'
                ];
                continue;
            }
            
            // Kërko në OpenFDA API
            try {
                $response = Http::retry(3, 100)
                    ->timeout(10)
                    ->get("https://api.fda.gov/drug/ndc.json?search=product_ndc:\"{$code}\"");
                
                if ($response->successful() && $response->json('results')) {
                    $data = $response->json('results')[0];
                    
                    $product = Product::create([
                        'ndc_code' => $code,
                        'brand_name' => $data['brand_name'] ?? null,
                        'generic_name' => $data['generic_name'] ?? null,
                        'labeler_name' => $data['labeler_name'] ?? null,
                        'product_type' => $data['product_type'] ?? null,
                    ]);
                    
                    $this->results[] = [
                        'ndc_code' => $code,
                        'brand_name' => $product->brand_name ?? '-',
                        'labeler_name' => $product->labeler_name ?? '-',
                        'product_type' => $product->product_type ?? '-',
                        'source' => 'OpenFDA'
                    ];
                } else {
                    $this->results[] = [
                        'ndc_code' => $code,
                        'brand_name' => '-',
                        'labeler_name' => '-',
                        'product_type' => '-',
                        'source' => 'Nuk u Gjet'
                    ];
                }
            } catch (\Exception $e) {
                $this->error = 'Gabim në aksesimin e API: ' . $e->getMessage();
                $this->results[] = [
                    'ndc_code' => $code,
                    'brand_name' => '-',
                    'labeler_name' => '-',
                    'product_type' => '-',
                    'source' => 'Gabim API'
                ];
            }
        }
        
        $this->isSearching = false;
    }

    public function render()
    {
        return view('livewire.search-products');
    }
}

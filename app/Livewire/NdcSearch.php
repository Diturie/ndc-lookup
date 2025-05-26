<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class NdcSearch extends Component
{
    public $searchTerm = '';
    public $results = [];
    public $isLoading = false;

    public function mount()
    {
        $this->results = collect(session('search_results', []));
    }

    public function search()
    {
        $this->isLoading = true;
        
        if (empty($this->searchTerm)) {
            $this->isLoading = false;
            return;
        }

        try {
            // Check local database first
            $localProduct = Product::where('ndc_code', $this->searchTerm)->first();
            
            if ($localProduct && $localProduct->source === 'Database') {
                // Only show Database if it was originally added to our database (not from OpenFDA)
                $result = [
                    'ndc_code' => $localProduct->ndc_code,
                    'brand_name' => $localProduct->brand_name,
                    'generic_name' => $localProduct->generic_name,
                    'labeler_name' => $localProduct->labeler_name,
                    'product_type' => $localProduct->product_type,
                    'source' => 'Database'
                ];
                
                $this->results = collect([$result])->concat($this->results);
            } else {
                // Either not in database or it's from OpenFDA, try OpenFDA API
                $url = 'https://api.fda.gov/drug/ndc.json';
                
                // Try different NDC formats
                $ndcFormats = [
                    $this->searchTerm,  // Original format
                    str_replace('-', '', $this->searchTerm), // Without hyphen
                    substr_replace($this->searchTerm, '-', 4, 0), // Add hyphen at position 4
                ];

                $found = false;
                foreach ($ndcFormats as $ndcFormat) {
                    $params = [
                        'search' => "product_ndc:\"$ndcFormat\""
                    ];

                    Log::info('Trying NDC format', [
                        'format' => $ndcFormat,
                        'url' => $url,
                        'params' => $params
                    ]);

                    $response = Http::get($url, $params);
                    $responseData = $response->json();

                    Log::info('OpenFDA API Response', [
                        'status' => $response->status(),
                        'body' => $responseData
                    ]);

                    if ($response->successful() && !empty($responseData['results'])) {
                        $found = true;
                        $fdaData = $responseData['results'][0];

                        // If not already in database, save it
                        if (!$localProduct) {
                            $product = new Product();
                            $product->ndc_code = $this->searchTerm;
                            $product->brand_name = $fdaData['brand_name'] ?? 'N/A';
                            $product->generic_name = $fdaData['generic_name'] ?? 'N/A';
                            $product->labeler_name = $fdaData['labeler_name'] ?? 'N/A';
                            $product->product_type = $fdaData['product_type'] ?? 'N/A';
                            $product->source = 'OpenFDA';
                            $product->save();
                        }

                        $result = [
                            'ndc_code' => $this->searchTerm,
                            'brand_name' => $fdaData['brand_name'] ?? 'N/A',
                            'generic_name' => $fdaData['generic_name'] ?? 'N/A',
                            'labeler_name' => $fdaData['labeler_name'] ?? 'N/A',
                            'product_type' => $fdaData['product_type'] ?? 'N/A',
                            'source' => 'OpenFDA'
                        ];
                        
                        $this->results = collect([$result])->concat($this->results);
                        break;
                    }
                }

                if (!$found) {
                    // Not found in any format
                    $result = [
                        'ndc_code' => $this->searchTerm,
                        'brand_name' => 'N/A',
                        'generic_name' => 'N/A',
                        'labeler_name' => 'N/A',
                        'product_type' => 'N/A',
                        'source' => 'Not Found'
                    ];
                    
                    $this->results = collect([$result])->concat($this->results);
                }
            }

            // Update session
            session(['search_results' => $this->results->all()]);

        } catch (\Exception $e) {
            Log::error('Error in NDC search', [
                'ndc' => $this->searchTerm,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Handle error case
            $result = [
                'ndc_code' => $this->searchTerm,
                'brand_name' => 'N/A',
                'generic_name' => 'N/A',
                'labeler_name' => 'N/A',
                'product_type' => 'N/A',
                'source' => 'Error'
            ];
            $this->results = collect([$result])->concat($this->results);
        }

        $this->searchTerm = '';
        $this->isLoading = false;
    }

    public function clearResults()
    {
        $this->results = collect([]);
        session(['search_results' => []]);
    }

    public function deleteNdc($ndcCode)
    {
        // Delete from database if it exists
        Product::where('ndc_code', $ndcCode)->delete();
        
        // Remove from results
        $this->results = $this->results->reject(function ($item) use ($ndcCode) {
            return $item['ndc_code'] === $ndcCode;
        });
        
        // Update session
        session(['search_results' => $this->results->all()]);
    }

    public function exportToCsv()
    {
        if ($this->results->isEmpty()) {
            return;
        }

        $filename = 'ndc-search-results.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NDC Code', 'Brand Name', 'Generic Name', 'Labeler', 'Product Type', 'Source']);
            
            foreach ($this->results as $row) {
                fputcsv($file, [
                    $row['ndc_code'],
                    $row['brand_name'],
                    $row['generic_name'],
                    $row['labeler_name'],
                    $row['product_type'],
                    $row['source']
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.ndc-search')->layout('layouts.app');
    }
}

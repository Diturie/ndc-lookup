<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class OpenFdaService
{
    protected $baseUrl = 'https://api.fda.gov/drug/ndc.json';

    public function searchNdc(string $ndcCode): ?array
    {
        try {
            $response = Http::get($this->baseUrl, [
                'search' => 'product_ndc:"' . $ndcCode . '"'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['results']) && count($data['results']) > 0) {
                    $result = $data['results'][0];
                    
                    return [
                        'ndc_code' => $result['product_ndc'] ?? null,
                        'brand_name' => $result['brand_name'] ?? null,
                        'generic_name' => $result['generic_name'] ?? null,
                        'labeler_name' => $result['labeler_name'] ?? null,
                        'product_type' => $result['product_type'] ?? null
                    ];
                }
            }
            
            return null;
        } catch (Exception $e) {
            // Log the error but return null to handle gracefully
            \Log::error('OpenFDA API Error: ' . $e->getMessage());
            return null;
        }
    }
} 
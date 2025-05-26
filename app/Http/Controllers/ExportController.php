<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExportController extends Controller
{
    public function exportCsv(Request $request)
    {
        $searchTerm = $request->query('searchTerm');
        $ndcCodes = array_map('trim', explode(',', $searchTerm));
        $ndcCodes = array_filter($ndcCodes);
        
        if (empty($ndcCodes)) {
            return back()->with('error', 'No NDC codes provided for export');
        }

        $results = [];
        
        // Check database first
        foreach ($ndcCodes as $code) {
            $dbResult = Product::where('ndc_code', $code)->first();
            if ($dbResult) {
                $results[] = [
                    'ndc_code' => $dbResult->ndc_code,
                    'brand_name' => $dbResult->brand_name,
                    'generic_name' => $dbResult->generic_name,
                    'labeler_name' => $dbResult->labeler_name,
                    'product_type' => $dbResult->product_type,
                    'source' => 'Database'
                ];
                continue;
            }
        }

        // Query OpenFDA for remaining codes
        $remainingCodes = array_diff($ndcCodes, array_column($results, 'ndc_code'));
        
        if (!empty($remainingCodes)) {
            try {
                $searchQuery = implode(' OR ', array_map(function($code) {
                    return "product_ndc:\"$code\"";
                }, $remainingCodes));

                $response = Http::get('https://api.fda.gov/drug/ndc.json', [
                    'search' => $searchQuery,
                    'limit' => count($remainingCodes)
                ]);

                if ($response->successful() && isset($response['results'])) {
                    foreach ($response['results'] as $result) {
                        $results[] = [
                            'ndc_code' => $result['product_ndc'] ?? 'N/A',
                            'brand_name' => $result['brand_name'] ?? 'N/A',
                            'generic_name' => $result['generic_name'] ?? 'N/A',
                            'labeler_name' => $result['labeler_name'] ?? 'N/A',
                            'product_type' => $result['product_type'] ?? 'N/A',
                            'source' => 'OpenFDA'
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Handle API errors silently
            }

            // Add "Not Found" results
            $foundCodes = array_column($results, 'ndc_code');
            foreach ($remainingCodes as $code) {
                if (!in_array($code, $foundCodes)) {
                    $results[] = [
                        'ndc_code' => $code,
                        'brand_name' => 'N/A',
                        'generic_name' => 'N/A',
                        'labeler_name' => 'N/A',
                        'product_type' => 'N/A',
                        'source' => 'Not Found'
                    ];
                }
            }
        }

        // Generate CSV
        $filename = 'ndc-search-results-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NDC Code', 'Brand Name', 'Generic Name', 'Labeler', 'Product Type', 'Source']);
            
            foreach ($results as $row) {
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
} 
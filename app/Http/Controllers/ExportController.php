<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * ExportController handles the export functionality for NDC lookup results
 * This controller manages exporting search results to CSV format, combining
 * data from both the local database and OpenFDA API
 */
class ExportController extends Controller
{
    /**
     * Export NDC lookup results to CSV format
     * This method handles the entire export process:
     * 1. Retrieves data from local database
     * 2. Queries OpenFDA API for missing data
     * 3. Generates and streams a CSV file
     *
     * @param Request $request Contains search terms in query parameter
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|Illuminate\Http\RedirectResponse
     */
    public function exportCsv(Request $request)
    {
        // Get and clean NDC codes from the search term
        $searchTerm = $request->query('searchTerm');
        $ndcCodes = array_map('trim', explode(',', $searchTerm));
        $ndcCodes = array_filter($ndcCodes);
        
        // Validate input
        if (empty($ndcCodes)) {
            return back()->with('error', 'No NDC codes provided for export');
        }

        $results = [];
        
        // First phase: Check local database for existing records
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

        // Second phase: Query OpenFDA API for codes not found in database
        $remainingCodes = array_diff($ndcCodes, array_column($results, 'ndc_code'));
        
        if (!empty($remainingCodes)) {
            try {
                // Construct OpenFDA API query for multiple NDC codes
                $searchQuery = implode(' OR ', array_map(function($code) {
                    return "product_ndc:\"$code\"";
                }, $remainingCodes));

                // Make API request
                $response = Http::get('https://api.fda.gov/drug/ndc.json', [
                    'search' => $searchQuery,
                    'limit' => count($remainingCodes)
                ]);

                // Process API results
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
                // Silently handle API errors to ensure export continues
            }

            // Third phase: Add entries for codes not found in either source
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

        // Set up CSV file headers
        $filename = 'ndc-search-results-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // Create streaming response callback
        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            // Write CSV header row
            fputcsv($file, ['NDC Code', 'Brand Name', 'Generic Name', 'Labeler', 'Product Type', 'Source']);
            
            // Write data rows
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

        // Return streaming response
        return response()->stream($callback, 200, $headers);
    }
} 
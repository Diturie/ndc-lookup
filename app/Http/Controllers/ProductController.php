<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OpenFdaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ProductController handles all NDC (National Drug Code) product-related operations
 * This controller manages searching, retrieving, and caching drug information
 * from both local database and OpenFDA API
 */
class ProductController extends Controller
{
    protected $openFdaService;

    /**
     * Constructor - Inject the OpenFDA service dependency
     */
    public function __construct(OpenFdaService $openFdaService)
    {
        $this->openFdaService = $openFdaService;
    }

    /**
     * Search for products based on NDC codes
     * This method handles both single and multiple NDC code searches
     * It first checks the local database, then queries OpenFDA API for missing codes
     * 
     * @param Request $request Contains NDC codes in comma-separated format
     * @return \Illuminate\View\View Returns view with search results
     */
    public function search(Request $request)
    {
        $request->validate([
            'ndc' => 'required|string|max:255'
        ]);

        // Split the input string into individual NDC codes and remove whitespace
        $ndcCodes = array_map('trim', explode(',', $request->input('ndc')));
        $allResults = $request->session()->get('search_results', []);
        $newResults = [];

        // First, check our database for existing records
        $databaseProducts = Product::whereIn('ndc_code', $ndcCodes)->get();
        $foundCodes = $databaseProducts->pluck('ndc_code')->toArray();
        
        // Add products found in database to results
        foreach ($databaseProducts as $product) {
            $newResults[] = array_merge($product->toArray(), ['source' => 'Database']);
        }

        // Determine which codes need to be looked up in the API
        $codesForApi = array_diff($ndcCodes, $foundCodes);
        
        if (!empty($codesForApi)) {
            // Batch API call for efficiency
            $openFdaResults = $this->searchMultipleNdc($codesForApi);
            
            foreach ($codesForApi as $ndcCode) {
                if (isset($openFdaResults[$ndcCode])) {
                    // Save new products to database and add to results
                    $product = Product::create($openFdaResults[$ndcCode]);
                    $newResults[] = array_merge($product->toArray(), ['source' => 'OpenFDA']);
                } else {
                    // Add not found products to results
                    $newResults[] = [
                        'ndc_code' => $ndcCode,
                        'brand_name' => '-',
                        'generic_name' => '-',
                        'labeler_name' => '-',
                        'product_type' => '-',
                        'source' => 'Not Found'
                    ];
                }
            }
        }

        // Combine new results with existing ones (new results first)
        $allResults = array_merge($newResults, $allResults);
        
        // Store in session for persistence
        $request->session()->put('search_results', $allResults);

        return view('products.search', [
            'results' => $allResults,
            'searchTerm' => '' // Reset search term
        ]);
    }

    /**
     * Perform a batch search for multiple NDC codes using OpenFDA API
     * This method optimizes API calls by searching for multiple codes at once
     * 
     * @param array $ndcCodes Array of NDC codes to search for
     * @return array Associative array of found products, keyed by NDC code
     */
    private function searchMultipleNdc(array $ndcCodes): array
    {
        if (empty($ndcCodes)) {
            return [];
        }

        // Log API call for monitoring
        \Log::info('Making OpenFDA API call for codes: ' . implode(', ', $ndcCodes));

        // Construct OpenFDA API query for multiple NDC codes
        $searchQuery = implode(' OR ', array_map(function($code) {
            return "product_ndc:\"$code\"";
        }, $ndcCodes));

        try {
            $response = Http::get('https://api.fda.gov/drug/ndc.json', [
                'search' => $searchQuery,
                'limit' => count($ndcCodes)
            ]);

            if ($response->successful() && isset($response['results'])) {
                $results = [];
                foreach ($response['results'] as $result) {
                    $ndcCode = $result['product_ndc'] ?? null;
                    if ($ndcCode) {
                        $results[$ndcCode] = [
                            'ndc_code' => $ndcCode,
                            'brand_name' => $result['brand_name'] ?? null,
                            'generic_name' => $result['generic_name'] ?? null,
                            'labeler_name' => $result['labeler_name'] ?? null,
                            'product_type' => $result['product_type'] ?? null
                        ];
                    }
                }
                \Log::info('Successfully retrieved data for codes: ' . implode(', ', array_keys($results)));
                return $results;
            }
        } catch (\Exception $e) {
            \Log::error('OpenFDA API Error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Validate NDC code format
     * Ensures the NDC code matches the standard format (XXXX-XXXX or XXXXX-XXXX)
     * 
     * @param string $ndc NDC code to validate
     * @return bool True if format is valid
     */
    private function isValidNdcFormat(string $ndc): bool
    {
        return preg_match('/^[0-9A-Za-z]{4,5}-[0-9A-Za-z]{3,4}$/', $ndc);
    }

    /**
     * Search OpenFDA API for a single NDC code
     * Includes retry logic and timeout handling
     * 
     * @param string $ndc NDC code to search
     * @return array Search result with product data if found
     */
    private function searchOpenFdaApi(string $ndc): array
    {
        $response = Http::retry(3, 100)
            ->timeout(10)
            ->get("https://api.fda.gov/drug/ndc.json?search=product_ndc:\"{$ndc}\"");

        if ($response->successful() && $response->json('results')) {
            $data = $response->json('results')[0];
            
            $product = Product::create([
                'ndc_code' => $ndc,
                'brand_name' => $data['brand_name'] ?? null,
                'generic_name' => $data['generic_name'] ?? null,
                'labeler_name' => $data['labeler_name'] ?? null,
                'product_type' => $data['product_type'] ?? null,
            ]);
            
            return ['found' => true, 'product' => $product];
        }

        return ['found' => false];
    }

    /**
     * Format product data for view display
     * Standardizes the output format and handles missing data
     * 
     * @param Product $product Product model instance
     * @param string $source Data source identifier
     * @return array Formatted product data
     */
    private function formatProductResult(Product $product, string $source): array
    {
        return [
            'ndc_code' => $product->ndc_code,
            'brand_name' => $product->brand_name ?? '-',
            'labeler_name' => $product->labeler_name ?? '-',
            'product_type' => $product->product_type ?? '-',
            'source' => $source
        ];
    }

    /**
     * Create a standardized "not found" result
     * Used when a product cannot be found in either database or API
     * 
     * @param string $ndc NDC code that wasn't found
     * @param string $reason Reason for not finding the product
     * @return array Formatted "not found" result
     */
    private function createNotFoundResult(string $ndc, string $reason): array
    {
        return [
            'ndc_code' => $ndc,
            'brand_name' => '-',
            'labeler_name' => '-',
            'product_type' => '-',
            'source' => $reason
        ];
    }

    /**
     * Display the search form view
     * Includes any existing search results from the session
     * 
     * @return \Illuminate\View\View
     */
    public function showSearchForm()
    {
        // Retrieve existing results from session
        $results = session('search_results', []);
        return view('products.search', [
            'results' => $results,
            'searchTerm' => ''
        ]);
    }
}
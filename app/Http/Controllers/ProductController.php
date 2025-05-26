<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OpenFdaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $openFdaService;

    public function __construct(OpenFdaService $openFdaService)
    {
        $this->openFdaService = $openFdaService;
    }

    /**
     * Kryen kërkimin e produkteve bazuar në kodet NDC
     * 
     * @param Request $request Request object që përmban kodet NDC
     * @return \Illuminate\View\View Pamja me rezultatet e kërkimit
     */
    public function search(Request $request)
    {
        $request->validate([
            'ndc' => 'required|string|max:255'
        ]);

        // Split and clean NDC codes
        $ndcCodes = array_map('trim', explode(',', $request->input('ndc')));
        $allResults = $request->session()->get('search_results', []);
        $newResults = [];

        // Group NDC codes by source (database vs need API lookup)
        $databaseProducts = Product::whereIn('ndc_code', $ndcCodes)->get();
        $foundCodes = $databaseProducts->pluck('ndc_code')->toArray();
        
        // Add database results
        foreach ($databaseProducts as $product) {
            $newResults[] = array_merge($product->toArray(), ['source' => 'Database']);
        }

        // Get codes that need API lookup
        $codesForApi = array_diff($ndcCodes, $foundCodes);
        
        if (!empty($codesForApi)) {
            // Make a single API call for all remaining codes
            $openFdaResults = $this->searchMultipleNdc($codesForApi);
            
            foreach ($codesForApi as $ndcCode) {
                if (isset($openFdaResults[$ndcCode])) {
                    // Save to database and add to results
                    $product = Product::create($openFdaResults[$ndcCode]);
                    $newResults[] = array_merge($product->toArray(), ['source' => 'OpenFDA']);
                } else {
                    // Not found in API
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

        // Add new results to the beginning of all results
        $allResults = array_merge($newResults, $allResults);
        
        // Store results in session
        $request->session()->put('search_results', $allResults);

        return view('products.search', [
            'results' => $allResults,
            'searchTerm' => '' // Clear the search term
        ]);
    }

    /**
     * Search multiple NDC codes in a single API call
     */
    private function searchMultipleNdc(array $ndcCodes): array
    {
        if (empty($ndcCodes)) {
            return [];
        }

        // Log that we're making an API call
        \Log::info('Making OpenFDA API call for codes: ' . implode(', ', $ndcCodes));

        // Build the search query for multiple NDC codes
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
     * Validon formatin e kodit NDC
     * 
     * @param string $ndc Kodi NDC për validim
     * @return bool True nëse formati është valid
     */
    private function isValidNdcFormat(string $ndc): bool
    {
        return preg_match('/^[0-9A-Za-z]{4,5}-[0-9A-Za-z]{3,4}$/', $ndc);
    }

    /**
     * Kërkon produktin në OpenFDA API
     * 
     * @param string $ndc Kodi NDC për kërkim
     * @return array Rezultati i kërkimit me produktin (nëse u gjet)
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
     * Formatton rezultatin e produktit për pamjen
     * 
     * @param Product $product Modeli i produktit
     * @param string $source Burimi i të dhënave
     * @return array Rezultati i formatuar
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
     * Krijon një rezultat kur produkti nuk gjendet
     * 
     * @param string $ndc Kodi NDC
     * @param string $source Arsyeja e mosgjetjes
     * @return array Rezultati i formatuar
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
     * Show the search form
     */
    public function showSearchForm()
    {
        // Get existing results from session
        $results = session('search_results', []);
        return view('products.search', [
            'results' => $results,
            'searchTerm' => ''
        ]);
    }
}
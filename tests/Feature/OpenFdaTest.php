<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\OpenFdaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpenFdaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'api.fda.gov/drug/ndc.json*' => Http::response([
                'results' => [
                    [
                        'product_ndc' => '0002-1433',
                        'brand_name' => 'Test Brand',
                        'generic_name' => 'Test Generic',
                        'labeler_name' => 'Test Labeler',
                        'product_type' => 'Test Type'
                    ]
                ]
            ], 200)
        ]);
    }

    public function test_can_search_ndc_code()
    {
        $service = new OpenFdaService();
        $result = $service->searchNdc('0002-1433');

        $this->assertNotNull($result);
        $this->assertEquals('0002-1433', $result['ndc_code']);
        $this->assertEquals('Test Brand', $result['brand_name']);
        $this->assertEquals('Test Generic', $result['generic_name']);
        $this->assertEquals('Test Labeler', $result['labeler_name']);
        $this->assertEquals('Test Type', $result['product_type']);
    }

    public function test_returns_null_for_invalid_ndc()
    {
        Http::fake([
            'api.fda.gov/drug/ndc.json*' => Http::response([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No matches found!'
                ]
            ], 404)
        ]);

        $service = new OpenFdaService();
        $result = $service->searchNdc('invalid-ndc');

        $this->assertNull($result);
    }

    public function test_can_search_multiple_ndc_codes()
    {
        Http::fake([
            'api.fda.gov/drug/ndc.json*' => Http::response([
                'results' => [
                    [
                        'product_ndc' => '0002-1433',
                        'brand_name' => 'Test Brand 1',
                        'generic_name' => 'Test Generic 1',
                        'labeler_name' => 'Test Labeler 1',
                        'product_type' => 'Test Type 1'
                    ],
                    [
                        'product_ndc' => '0002-3227',
                        'brand_name' => 'Test Brand 2',
                        'generic_name' => 'Test Generic 2',
                        'labeler_name' => 'Test Labeler 2',
                        'product_type' => 'Test Type 2'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->post('/products/search', [
            'ndc' => '0002-1433,0002-3227'
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('results');
        
        $results = $response->viewData('results');
        $this->assertCount(2, $results);
        
        // Verify first result
        $this->assertEquals('0002-1433', $results[0]['ndc_code']);
        $this->assertEquals('Test Brand 1', $results[0]['brand_name']);
        
        // Verify second result
        $this->assertEquals('0002-3227', $results[1]['ndc_code']);
        $this->assertEquals('Test Brand 2', $results[1]['brand_name']);
    }

    public function test_handles_api_error_gracefully()
    {
        Http::fake([
            'api.fda.gov/drug/ndc.json*' => Http::response([
                'error' => 'API Error'
            ], 500)
        ]);

        $response = $this->post('/products/search', [
            'ndc' => '0002-1433'
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('results');
        
        $results = $response->viewData('results');
        $this->assertCount(1, $results);
        $this->assertEquals('Not Found', $results[0]['source']);
    }
} 
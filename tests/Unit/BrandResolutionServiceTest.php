<?php

namespace Tests\Unit;

use App\Services\BrandResolutionService;
use PHPUnit\Framework\TestCase;

class BrandResolutionServiceTest extends TestCase
{
    /**
     * Test: Get brand from source path - professional
     */
    public function test_get_brand_from_source_path_professional()
    {
        $brand = BrandResolutionService::getBrandFromSourcePath('/professional/order');
        $this->assertEquals('professional', $brand);
    }

    /**
     * Test: Get brand from source path - cccportal (preserved as separate brand)
     */
    public function test_get_brand_from_source_path_cccportal()
    {
        $brand = BrandResolutionService::getBrandFromSourcePath('/cccportal/order');
        $this->assertEquals('cccportal', $brand);
    }

    /**
     * Test: Get brand from source path - pinksky
     */
    public function test_get_brand_from_source_path_pinksky()
    {
        $brand = BrandResolutionService::getBrandFromSourcePath('/pinksky/order');
        $this->assertEquals('pinksky', $brand);
    }

    /**
     * Test: Get brand from source path - null/empty
     */
    public function test_get_brand_from_source_path_null()
    {
        $brand = BrandResolutionService::getBrandFromSourcePath(null);
        $this->assertNull($brand);
        
        $brand = BrandResolutionService::getBrandFromSourcePath('');
        $this->assertNull($brand);
    }

    /**
     * Test: Get brand from source path - invalid path
     */
    public function test_get_brand_from_source_path_invalid()
    {
        $brand = BrandResolutionService::getBrandFromSourcePath('/products/order');
        $this->assertNull($brand);
    }

    /**
     * Test: Get currency from source path - professional
     */
    public function test_get_currency_from_source_path_professional()
    {
        $currency = BrandResolutionService::getCurrencyFromSourcePath('/professional/order');
        $this->assertEquals('CAD', $currency);
    }

    /**
     * Test: Get currency from source path - cccportal
     */
    public function test_get_currency_from_source_path_cccportal()
    {
        $currency = BrandResolutionService::getCurrencyFromSourcePath('/cccportal/order');
        $this->assertEquals('CAD', $currency);
    }

    /**
     * Test: Get currency from source path - pinksky
     */
    public function test_get_currency_from_source_path_pinksky()
    {
        $currency = BrandResolutionService::getCurrencyFromSourcePath('/pinksky/order');
        $this->assertEquals('USD', $currency);
    }

    /**
     * Test: Get currency from source path - null/empty
     */
    public function test_get_currency_from_source_path_null()
    {
        $currency = BrandResolutionService::getCurrencyFromSourcePath(null);
        $this->assertNull($currency);
    }

    /**
     * Test: Get currency from source path - invalid path
     */
    public function test_get_currency_from_source_path_invalid()
    {
        $currency = BrandResolutionService::getCurrencyFromSourcePath('/products/order');
        $this->assertNull($currency);
    }

    /**
     * Test: Get brand from currency - CAD
     */
    public function test_get_brand_from_currency_cad()
    {
        $brand = BrandResolutionService::getBrandFromCurrency('CAD');
        $this->assertEquals('professional', $brand);
    }

    /**
     * Test: Get brand from currency - USD
     */
    public function test_get_brand_from_currency_usd()
    {
        $brand = BrandResolutionService::getBrandFromCurrency('USD');
        $this->assertEquals('pinksky', $brand);
    }

    /**
     * Test: Get brand from currency - null
     */
    public function test_get_brand_from_currency_null()
    {
        $brand = BrandResolutionService::getBrandFromCurrency(null);
        $this->assertNull($brand);
    }

    /**
     * Test: Get brand from currency - invalid
     */
    public function test_get_brand_from_currency_invalid()
    {
        $brand = BrandResolutionService::getBrandFromCurrency('GBP');
        $this->assertNull($brand);
    }

    /**
     * Test: Resolve brand - source path takes priority
     */
    public function test_resolve_brand_source_path_priority()
    {
        // Even if currency says USD (pinksky), source path says professional
        $brand = BrandResolutionService::resolveBrand('/professional/order', 'USD');
        $this->assertEquals('professional', $brand);
    }

    /**
     * Test: Resolve brand - fall back to currency
     */
    public function test_resolve_brand_fall_back_to_currency()
    {
        $brand = BrandResolutionService::resolveBrand(null, 'CAD');
        $this->assertEquals('professional', $brand);
    }

    /**
     * Test: Resolve brand - null source path and currency
     */
    public function test_resolve_brand_null()
    {
        $brand = BrandResolutionService::resolveBrand(null, null);
        $this->assertNull($brand);
    }

    /**
     * Test: Resolve currency - source path takes priority
     */
    public function test_resolve_currency_source_path_priority()
    {
        // Even if currency says USD, source path says CAD
        $currency = BrandResolutionService::resolveCurrency('/professional/order', 'USD');
        $this->assertEquals('CAD', $currency);
    }

    /**
     * Test: Resolve currency - fall back to provided currency
     */
    public function test_resolve_currency_fall_back()
    {
        $currency = BrandResolutionService::resolveCurrency(null, 'USD');
        $this->assertEquals('USD', $currency);
    }

    /**
     * Test: Validate currency - matching
     */
    public function test_validate_currency_matching()
    {
        // Should not throw exception
        BrandResolutionService::validateCurrency('/professional/order', 'CAD');
        $this->assertTrue(true); // If no exception, test passes
    }

    /**
     * Test: Validate currency - matching pinksky
     */
    public function test_validate_currency_matching_pinksky()
    {
        // Should not throw exception
        BrandResolutionService::validateCurrency('/pinksky/order', 'USD');
        $this->assertTrue(true); // If no exception, test passes
    }

    /**
     * Test: Validate currency - mismatching throws exception
     */
    public function test_validate_currency_mismatching()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Currency mismatch');
        
        // Professional requires CAD, but USD provided
        BrandResolutionService::validateCurrency('/professional/order', 'USD');
    }

    /**
     * Test: Validate currency - no source path
     */
    public function test_validate_currency_no_source_path()
    {
        // Should not throw exception (no validation if no source path)
        BrandResolutionService::validateCurrency(null, 'USD');
        $this->assertTrue(true);
    }

    /**
     * Test: Validate currency - no currency
     */
    public function test_validate_currency_no_currency()
    {
        // Should not throw exception (no validation if no currency)
        BrandResolutionService::validateCurrency('/professional/order', null);
        $this->assertTrue(true);
    }

    /**
     * Test: URL parameter style source paths
     */
    public function test_url_parameter_source_paths()
    {
        // Test with query parameters
        $brand = BrandResolutionService::getBrandFromSourcePath('http://localhost:3000/professional/order?ref=abc123');
        $this->assertEquals('professional', $brand);
        
        $currency = BrandResolutionService::getCurrencyFromSourcePath('http://localhost:3000/professional/order?ref=abc123');
        $this->assertEquals('CAD', $currency);
    }

    /**
     * Test: Full URLs with source paths
     */
    public function test_full_urls()
    {
        // Test with full URLs
        $brand = BrandResolutionService::getBrandFromSourcePath('https://example.com/pinksky/order');
        $this->assertEquals('pinksky', $brand);
        
        $currency = BrandResolutionService::getCurrencyFromSourcePath('https://example.com/pinksky/order');
        $this->assertEquals('USD', $currency);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\BrandTaxConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTaxConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        config([
            'checkout.tax_rate' => 13,
            'checkout.shipping_fee' => 60,
            'checkout.brands' => [
                'pinksky' => [
                    'tax_rate' => 5,
                    'currency' => 'USD',
                    'shipping_fee' => null,
                ],
                'cccportal' => [
                    'tax_rate' => 3,
                    'currency' => 'CAD',
                    'shipping_fee' => null,
                ],
                'professional' => [
                    'tax_rate' => 13,
                    'currency' => 'CAD',
                    'shipping_fee' => null,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_returns_pinksky_tax_rate()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'pinksky');
        
        $this->assertEquals(5.0, $taxRate);
    }

    /** @test */
    public function it_returns_cccportal_tax_rate()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'cccportal');
        
        $this->assertEquals(3.0, $taxRate);
    }

    /** @test */
    public function it_returns_professional_tax_rate()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'professional');
        
        $this->assertEquals(13.0, $taxRate);
    }

    /** @test */
    public function it_prioritizes_product_specific_tax_rate_over_brand()
    {
        $product = Product::factory()->create(['tax_rate' => 7.5]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'pinksky');
        
        $this->assertEquals(7.5, $taxRate);
    }

    /** @test */
    public function it_falls_back_to_global_tax_rate_when_brand_not_found()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'unknown_brand');
        
        $this->assertEquals(13.0, $taxRate);
    }

    /** @test */
    public function it_falls_back_to_global_tax_rate_when_no_brand_provided()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, null);
        
        $this->assertEquals(13.0, $taxRate);
    }

    /** @test */
    public function it_returns_correct_shipping_fee_for_brand()
    {
        $product = Product::factory()->create(['shipping_fee' => null]);
        
        $shippingFee = BrandTaxConfigService::getShippingFee($product, 'pinksky');
        
        $this->assertEquals(60.0, $shippingFee); // Falls back to global
    }

    /** @test */
    public function it_prioritizes_product_specific_shipping_fee()
    {
        $product = Product::factory()->create(['shipping_fee' => 45.0]);
        
        $shippingFee = BrandTaxConfigService::getShippingFee($product, 'pinksky');
        
        $this->assertEquals(45.0, $shippingFee);
    }

    /** @test */
    public function it_returns_complete_brand_config()
    {
        $product = Product::factory()->create([
            'tax_rate' => null,
            'shipping_fee' => null,
        ]);
        
        $config = BrandTaxConfigService::getBrandConfig($product, 'pinksky', 'USD');
        
        $this->assertIsArray($config);
        $this->assertEquals(5.0, $config['tax_rate']);
        $this->assertEquals(60.0, $config['shipping_fee']);
        $this->assertEquals('pinksky', $config['brand']);
        $this->assertEquals('USD', $config['currency']);
    }

    /** @test */
    public function it_resolves_brand_from_currency_in_config()
    {
        $product = Product::factory()->create([
            'tax_rate' => null,
            'shipping_fee' => null,
        ]);
        
        // Should resolve to pinksky for USD
        $config = BrandTaxConfigService::getBrandConfig($product, null, 'USD');
        
        $this->assertEquals('pinksky', $config['brand']);
        $this->assertEquals(5.0, $config['tax_rate']);
    }

    /** @test */
    public function it_returns_currency_for_brand()
    {
        $this->assertEquals('USD', BrandTaxConfigService::getCurrencyForBrand('pinksky'));
        $this->assertEquals('CAD', BrandTaxConfigService::getCurrencyForBrand('cccportal'));
        $this->assertEquals('CAD', BrandTaxConfigService::getCurrencyForBrand('professional'));
    }

    /** @test */
    public function it_validates_brand_config()
    {
        $this->assertTrue(BrandTaxConfigService::validateBrandConfig('pinksky'));
        $this->assertTrue(BrandTaxConfigService::validateBrandConfig('cccportal'));
        $this->assertTrue(BrandTaxConfigService::validateBrandConfig('professional'));
        $this->assertFalse(BrandTaxConfigService::validateBrandConfig('unknown_brand'));
    }

    /** @test */
    public function it_returns_all_brand_configs()
    {
        $configs = BrandTaxConfigService::getAllBrandConfigs();
        
        $this->assertIsArray($configs);
        $this->assertArrayHasKey('pinksky', $configs);
        $this->assertArrayHasKey('cccportal', $configs);
        $this->assertArrayHasKey('professional', $configs);
        
        $this->assertEquals(5, $configs['pinksky']['tax_rate']);
        $this->assertEquals(3, $configs['cccportal']['tax_rate']);
        $this->assertEquals(13, $configs['professional']['tax_rate']);
    }

    /** @test */
    public function it_returns_brand_display_names()
    {
        $this->assertEquals('Pinksky', BrandTaxConfigService::getBrandDisplayName('pinksky'));
        $this->assertEquals('CCC Portal', BrandTaxConfigService::getBrandDisplayName('cccportal'));
        $this->assertEquals('Professional', BrandTaxConfigService::getBrandDisplayName('professional'));
    }

    /** @test */
    public function it_returns_brand_tax_summary()
    {
        $summary = BrandTaxConfigService::getBrandTaxSummary();
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('pinksky', $summary);
        $this->assertArrayHasKey('cccportal', $summary);
        $this->assertArrayHasKey('professional', $summary);
        
        // Check pinksky
        $this->assertEquals('Pinksky', $summary['pinksky']['name']);
        $this->assertEquals(5, $summary['pinksky']['tax_rate']);
        $this->assertEquals('USD', $summary['pinksky']['currency']);
        
        // Check cccportal
        $this->assertEquals('CCC Portal', $summary['cccportal']['name']);
        $this->assertEquals(3, $summary['cccportal']['tax_rate']);
        $this->assertEquals('CAD', $summary['cccportal']['currency']);
        
        // Check professional
        $this->assertEquals('Professional', $summary['professional']['name']);
        $this->assertEquals(13, $summary['professional']['tax_rate']);
        $this->assertEquals('CAD', $summary['professional']['currency']);
    }

    /** @test */
    public function it_supports_legacy_config_structure()
    {
        // Clear new config and set legacy config
        config(['checkout.brands' => []]);
        config([
            'checkout.tax_rates_by_brand' => [
                'pinksky' => 5,
                'cccportal' => 3,
                'professional' => 13,
            ],
        ]);

        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'pinksky');
        
        $this->assertEquals(5.0, $taxRate);
    }

    /** @test */
    public function it_handles_mixed_product_and_brand_config()
    {
        // Product with custom tax rate but no shipping fee
        $product = Product::factory()->create([
            'tax_rate' => 8.5,
            'shipping_fee' => null,
        ]);
        
        $config = BrandTaxConfigService::getBrandConfig($product, 'pinksky', 'USD');
        
        // Should use product tax rate
        $this->assertEquals(8.5, $config['tax_rate']);
        // Should use global shipping fee
        $this->assertEquals(60.0, $config['shipping_fee']);
    }

    /** @test */
    public function it_handles_zero_tax_rate()
    {
        config(['checkout.brands.pinksky.tax_rate' => 0]);
        
        $product = Product::factory()->create(['tax_rate' => null]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'pinksky');
        
        $this->assertEquals(0.0, $taxRate);
    }

    /** @test */
    public function it_handles_product_with_zero_tax_rate()
    {
        $product = Product::factory()->create(['tax_rate' => 0]);
        
        $taxRate = BrandTaxConfigService::getTaxRate($product, 'pinksky');
        
        // Product-specific should take priority even if it's 0
        $this->assertEquals(0.0, $taxRate);
    }
}

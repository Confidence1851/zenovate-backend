<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * Centralized service for managing brand-specific tax and shipping configuration
 * 
 * This service ensures consistent application of tax rates and shipping fees across all brands:
 * 
 * Priority order for configuration:
 * 1. Product-specific settings (product.tax_rate, product.shipping_fee)
 * 2. Brand-specific settings (config/checkout.php brands array)
 * 3. Global defaults (config/checkout.php defaults)
 */
class BrandTaxConfigService
{
    /**
     * Get complete brand configuration for a product
     * 
     * @param Product $product The product to get configuration for
     * @param string|null $brand Brand identifier (pinksky, cccportal, professional)
     * @param string|null $currency Currency code (USD, CAD) - used for brand resolution if brand not provided
     * @param string|null $sourcePath Source path - used for brand resolution if brand not provided
     * @return array ['tax_rate' => float, 'shipping_fee' => float, 'brand' => string|null, 'currency' => string|null]
     */
    public static function getBrandConfig(
        Product $product,
        ?string $brand = null,
        ?string $currency = null,
        ?string $sourcePath = null
    ): array {
        // Resolve brand if not provided
        if (!$brand) {
            $brand = BrandResolutionService::resolveBrand($sourcePath, $currency);
        }

        // Get tax rate (priority: product > brand > global)
        $taxRate = self::getTaxRate($product, $brand);

        // Get shipping fee (priority: product > brand > global)
        $shippingFee = self::getShippingFee($product, $brand);

        // Get currency for this brand
        $resolvedCurrency = $currency ?? self::getCurrencyForBrand($brand);

        return [
            'tax_rate' => $taxRate,
            'shipping_fee' => $shippingFee,
            'brand' => $brand,
            'currency' => $resolvedCurrency,
        ];
    }

    /**
     * Get tax rate for a product
     * Priority: Product-specific > Brand-specific > Global default
     * 
     * @param Product $product
     * @param string|null $brand Brand identifier
     * @return float Tax rate as percentage (e.g., 5 for 5%)
     */
    public static function getTaxRate(Product $product, ?string $brand = null): float
    {
        // 1. Check product-specific tax rate first (highest priority)
        if ($product->tax_rate !== null) {
            return (float) $product->tax_rate;
        }

        // 2. Check brand-specific tax rate
        if ($brand) {
            $brandTaxRate = self::getTaxRateForBrand($brand);
            if ($brandTaxRate !== null) {
                return $brandTaxRate;
            }
        }

        // 3. Fall back to global default
        return (float) config('checkout.tax_rate', 0);
    }

    /**
     * Get shipping fee for a product
     * Priority: Product-specific > Brand-specific > Global default
     * 
     * @param Product $product
     * @param string|null $brand Brand identifier
     * @return float Shipping fee in base currency
     */
    public static function getShippingFee(Product $product, ?string $brand = null): float
    {
        // 1. Check product-specific shipping fee first (highest priority)
        if ($product->shipping_fee !== null) {
            return (float) $product->shipping_fee;
        }

        // 2. Check brand-specific shipping fee
        if ($brand) {
            $brandShippingFee = self::getShippingFeeForBrand($brand);
            if ($brandShippingFee !== null) {
                return $brandShippingFee;
            }
        }

        // 3. Fall back to global default
        return (float) config('checkout.shipping_fee', 60);
    }

    /**
     * Get tax rate for a specific brand
     * 
     * @param string $brand Brand identifier (pinksky, cccportal, professional)
     * @return float|null Tax rate or null if not configured
     */
    public static function getTaxRateForBrand(string $brand): ?float
    {
        $brandConfig = config("checkout.brands.{$brand}");
        
        if ($brandConfig && isset($brandConfig['tax_rate'])) {
            return (float) $brandConfig['tax_rate'];
        }

        // Fallback to old config structure for backward compatibility
        $legacyRate = config("checkout.tax_rates_by_brand.{$brand}");
        if ($legacyRate !== null) {
            return (float) $legacyRate;
        }

        return null;
    }

    /**
     * Get shipping fee for a specific brand
     * 
     * @param string $brand Brand identifier
     * @return float|null Shipping fee or null if not configured
     */
    public static function getShippingFeeForBrand(string $brand): ?float
    {
        $brandConfig = config("checkout.brands.{$brand}");
        
        if ($brandConfig && isset($brandConfig['shipping_fee'])) {
            return (float) $brandConfig['shipping_fee'];
        }

        return null;
    }

    /**
     * Get currency for a specific brand
     * 
     * @param string|null $brand Brand identifier
     * @return string|null Currency code (USD, CAD) or null
     */
    public static function getCurrencyForBrand(?string $brand): ?string
    {
        if (!$brand) {
            return null;
        }

        $brandConfig = config("checkout.brands.{$brand}");
        
        if ($brandConfig && isset($brandConfig['currency'])) {
            return $brandConfig['currency'];
        }

        // Fallback based on brand name
        if ($brand === 'pinksky') {
            return 'USD';
        } elseif (in_array($brand, ['cccportal', 'professional'])) {
            return 'CAD';
        }

        return null;
    }

    /**
     * Get all brand configurations
     * 
     * @return array Associative array of brand configurations
     */
    public static function getAllBrandConfigs(): array
    {
        $brands = config('checkout.brands', []);
        
        // Ensure backward compatibility with old config structure
        if (empty($brands)) {
            $taxRates = config('checkout.tax_rates_by_brand', []);
            $brands = [];
            
            foreach ($taxRates as $brand => $taxRate) {
                $brands[$brand] = [
                    'tax_rate' => $taxRate,
                    'currency' => self::getCurrencyForBrand($brand),
                    'shipping_fee' => null,
                ];
            }
        }

        return $brands;
    }

    /**
     * Validate that a brand has proper configuration
     * 
     * @param string $brand Brand identifier
     * @return bool True if brand is properly configured
     */
    public static function validateBrandConfig(string $brand): bool
    {
        $brandConfig = config("checkout.brands.{$brand}");
        
        if (!$brandConfig) {
            // Check legacy config
            $legacyRate = config("checkout.tax_rates_by_brand.{$brand}");
            return $legacyRate !== null;
        }

        // Brand config should have at least a tax rate
        return isset($brandConfig['tax_rate']);
    }

    /**
     * Get brand name for display purposes
     * 
     * @param string $brand Brand identifier
     * @return string Human-readable brand name
     */
    public static function getBrandDisplayName(string $brand): string
    {
        $names = [
            'pinksky' => 'Pinksky',
            'cccportal' => 'CCC Portal',
            'professional' => 'Professional',
        ];

        return $names[$brand] ?? ucfirst($brand);
    }

    /**
     * Log brand configuration for debugging
     * 
     * @param Product $product
     * @param string|null $brand
     * @param array $config
     * @return void
     */
    public static function logBrandConfig(Product $product, ?string $brand, array $config): void
    {
        Log::debug('Brand Tax Configuration', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'brand' => $brand,
            'config' => $config,
            'product_tax_rate' => $product->tax_rate,
            'product_shipping_fee' => $product->shipping_fee,
        ]);
    }

    /**
     * Get summary of all brand tax rates for display/debugging
     * 
     * @return array
     */
    public static function getBrandTaxSummary(): array
    {
        $brands = self::getAllBrandConfigs();
        $summary = [];

        foreach ($brands as $brandKey => $config) {
            $summary[$brandKey] = [
                'name' => self::getBrandDisplayName($brandKey),
                'tax_rate' => $config['tax_rate'] ?? 0,
                'currency' => $config['currency'] ?? 'N/A',
                'shipping_fee' => $config['shipping_fee'] ?? config('checkout.shipping_fee', 60),
            ];
        }

        return $summary;
    }
}

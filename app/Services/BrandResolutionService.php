<?php

namespace App\Services;

class BrandResolutionService
{
    /**
     * Get brand from source path (preserves cccportal and professional as separate brands)
     */
    public static function getBrandFromSourcePath(?string $sourcePath = null): ?string
    {
        if (!$sourcePath) {
            return null;
        }

        // Check for specific brands in order of priority
        if (str_contains($sourcePath, 'professional')) {
            return 'professional';
        } elseif (str_contains($sourcePath, 'cccportal')) {
            return 'cccportal';
        } elseif (str_contains($sourcePath, 'pinksky')) {
            return 'pinksky';
        }
        return null;
    }

    /**
     * Get currency from source path (cccportal, professional = CAD; pinksky = USD)
     */
    public static function getCurrencyFromSourcePath(?string $sourcePath = null): ?string
    {
        if (!$sourcePath) {
            return null;
        }

        if (str_contains($sourcePath, 'cccportal') || str_contains($sourcePath, 'professional')) {
            return 'CAD';
        } elseif (str_contains($sourcePath, 'pinksky')) {
            return 'USD';
        }
        return null;
    }

    /**
     * Get brand from currency (CAD = professional, others = pinksky)
     */
    public static function getBrandFromCurrency(?string $currency = null): ?string
    {
        if ($currency === 'CAD') {
            return 'professional';
        } elseif ($currency === 'USD') {
            return 'pinksky';
        }
        return null;
    }

    /**
     * Resolve brand from source path or currency (source path takes priority)
     */
    public static function resolveBrand(?string $sourcePath = null, ?string $currency = null): ?string
    {
        // Try source path first
        $brand = self::getBrandFromSourcePath($sourcePath);
        
        // Fall back to currency
        if (!$brand) {
            $brand = self::getBrandFromCurrency($currency);
        }
        
        return $brand;
    }

    /**
     * Resolve currency from source path or currency param (source path takes priority)
     */
    public static function resolveCurrency(?string $sourcePath = null, ?string $currency = null): ?string
    {
        // Try source path first
        $resolvedCurrency = self::getCurrencyFromSourcePath($sourcePath);
        
        // Fall back to provided currency
        if (!$resolvedCurrency && $currency) {
            $resolvedCurrency = $currency;
        }
        
        return $resolvedCurrency;
    }

    /**
     * Validate currency against source path
     * Throws exception if they don't match
     */
    public static function validateCurrency(?string $sourcePath = null, ?string $currency = null): void
    {
        if (!$sourcePath || !$currency) {
            return;
        }

        $enforcedCurrency = self::getCurrencyFromSourcePath($sourcePath);
        if ($enforcedCurrency && $currency !== $enforcedCurrency) {
            throw new \Exception("Currency mismatch: source path {$sourcePath} requires {$enforcedCurrency} but {$currency} was provided");
        }
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Shipping Fee
    |--------------------------------------------------------------------------
    |
    | Default shipping fee in the base currency (USD/CAD).
    | This can be overridden per product in the products table or by brand.
    |
    */
    'shipping_fee' => env('CHECKOUT_SHIPPING_FEE', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | Default tax rate as a percentage (e.g., 13 for 13%).
    | This is used as a fallback when no brand-specific or product-specific
    | tax rate is configured.
    |
    */
    'tax_rate' => env('CHECKOUT_TAX_RATE', 13),

    /*
    |--------------------------------------------------------------------------
    | Brand-Specific Configurations
    |--------------------------------------------------------------------------
    |
    | Complete configuration for each brand including tax rates, currency,
    | and optional shipping fees. This is the preferred configuration method.
    |
    | Brands:
    | - pinksky: USD-based, 5% tax
    | - cccportal: CAD-based, 3% tax
    | - professional: CAD-based, 13% tax
    |
    */
    'brands' => [
        'pinksky' => [
            'tax_rate' => env('CHECKOUT_TAX_RATE_PINKSKY', 5),
            'currency' => 'USD',
            'shipping_fee' => null, // null = use global default
            'display_name' => 'Pinksky',
        ],
        'cccportal' => [
            'tax_rate' => env('CHECKOUT_TAX_RATE_CCCPORTAL', 3),
            'currency' => 'CAD',
            'shipping_fee' => null,
            'display_name' => 'CCC Portal',
        ],
        'professional' => [
            'tax_rate' => env('CHECKOUT_TAX_RATE_PROFESSIONAL', 13),
            'currency' => 'CAD',
            'shipping_fee' => null,
            'display_name' => 'Professional',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Brand-Specific Tax Rates (Deprecated)
    |--------------------------------------------------------------------------
    |
    | DEPRECATED: Use 'brands' configuration above instead.
    | Kept for backward compatibility only.
    |
    */
    'tax_rates_by_brand' => [
        'pinksky' => env('CHECKOUT_TAX_RATE_PINKSKY', null),
        'cccportal' => env('CHECKOUT_TAX_RATE_CCCPORTAL', null),
        'professional' => env('CHECKOUT_TAX_RATE_PROFESSIONAL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping Fee by Country
    |--------------------------------------------------------------------------
    |
    | Country-specific shipping fees. If a country is not listed here,
    | the default shipping_fee will be used.
    |
    */
    'shipping_by_country' => [
        'US' => 60,
        'CA' => 60,
        // Add more countries as needed
    ],
];


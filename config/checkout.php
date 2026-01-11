<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Shipping Fee
    |--------------------------------------------------------------------------
    |
    | Default shipping fee in the base currency (USD/CAD).
    | This can be overridden per product in the products table.
    |
    */
    'shipping_fee' => env('CHECKOUT_SHIPPING_FEE', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | Default tax rate as a percentage (e.g., 5 for 5%).
    | This can be overridden per product in the products table.
    |
    */
    'tax_rate' => env('CHECKOUT_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Brand-Specific Tax Rates
    |--------------------------------------------------------------------------
    |
    | Tax rates by brand/checkout type (pinksky, cccportal).
    | If a brand is specified here, it will override the default tax_rate.
    |
    */
    'tax_rates_by_brand' => [
        'pinksky' => env('CHECKOUT_TAX_RATE_PINKSKY', null),
        'cccportal' => env('CHECKOUT_TAX_RATE_CCCPORTAL', null),
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


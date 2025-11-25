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


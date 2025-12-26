<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Sheet Currency
    |--------------------------------------------------------------------------
    |
    | The currency to use for order sheet pricing.
    | Set via ORDER_SHEET_CURRENCY environment variable.
    |
    */
    'currency' => env('ORDER_SHEET_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Use Location-Based Pricing
    |--------------------------------------------------------------------------
    |
    | If true, will switch to CAD for Canadian IPs.
    | If false, will use the currency setting above for all locations.
    | Set via ORDER_SHEET_USE_LOCATION_PRICING environment variable.
    |
    */
    'use_location_pricing' => env('ORDER_SHEET_USE_LOCATION_PRICING', false),
];

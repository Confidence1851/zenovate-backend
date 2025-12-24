<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Force Currency Override
    |--------------------------------------------------------------------------
    |
    | Set this to 'CAD' or 'USD' to force a specific currency for testing.
    | This overrides IP-based currency detection.
    |
    | Set to null or remove from .env to use automatic IP-based detection.
    |
    | Supported values: 'CAD', 'USD', null
    |
    */

    'force_currency' => env('FORCE_CURRENCY', null),

];

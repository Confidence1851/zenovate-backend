<?php

/**
 * Normalize URL to fix common formatting issues
 * Fixes URLs like "http://127.0.0.13000" to "http://127.0.0.1:3000"
 */
function normalizeFrontendUrl($url)
{
    if (empty($url)) {
        return $url;
    }

    // Fix missing colon in URLs like "http://127.0.0.13000" -> "http://127.0.0.1:3000"
    $url = preg_replace('/^((?:https?:\/\/)?127\.0\.0\.1)(\d+)$/', '$1:$2', $url);

    return $url;
}

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend Application URLs
    |--------------------------------------------------------------------------
    |
    | These URLs are used for redirecting users after payment processing
    | and other frontend-related redirects.
    |
    | FRONTEND_APP_SITE_URL: Main site URL (for direct checkout redirects)
    | FRONTEND_APP_URL: Form application URL (for form-based checkout redirects)
    |
    */

    'site_url' => normalizeFrontendUrl(env('FRONTEND_APP_SITE_URL', env('FRONTEND_APP_URL', 'http://localhost:3000'))),
    'form_url' => normalizeFrontendUrl(env('FRONTEND_APP_URL', 'http://localhost:3001')),
];

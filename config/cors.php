<?php

declare(strict_types=1);

/*
 * The front end is a separate deployment (Vercel) from the API (Laravel Cloud),
 * so the browser treats its requests as cross-origin and will not send the
 * session cookie without both an explicit origin allowlist and credentials
 * support.
 *
 * `allowed_origins` is an allowlist read from configuration, never `*`. A
 * wildcard is not merely lax here — browsers refuse to send credentials to a
 * wildcard origin at all, so `*` would silently break authentication as well as
 * being wrong.
 */

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000'))),
    ))),

    // Vercel gives every branch and pull request its own preview hostname, so
    // previews are matched by pattern rather than listed one at a time. Set
    // CORS_ALLOWED_ORIGIN_PATTERNS only where preview builds should reach a
    // given environment; production should normally leave it empty.
    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', '')),
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Correlation-Id'],

    'max_age' => 60 * 60 * 12,

    'supports_credentials' => true,

];

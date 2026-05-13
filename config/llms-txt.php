<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable the dynamic routes for serving llms.txt and
    | llms-full.txt. When enabled, the package registers routes that
    | serve the generated content on the fly.
    |
    */

    'route_enabled' => true,

    'llms_txt_route' => '/llms.txt',

    'llms_full_txt_route' => '/llms-full.txt',

    /*
    |--------------------------------------------------------------------------
    | Manual Route Registration
    |--------------------------------------------------------------------------
    |
    | When set to false, the service provider will NOT register the llms.txt
    | routes automatically during boot. You can then register them yourself
    | by calling LlmsTxt::routes() inside a route group of your choice — for
    | example to apply specific middleware or a custom URL prefix.
    |
    | Example (routes/web.php):
    |
    |   use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
    |
    |   Route::middleware(['web', 'cache.headers:public;max_age=3600'])
    |       ->group(function () {
    |           LlmsTxt::routes();
    |       });
    |
    */

    'register_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for the generated llms.txt output. When enabled,
    | the generated content is cached for the specified TTL in seconds.
    |
    */

    'cache_enabled' => true,

    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk used when generating static files via the
    | `llms:generate` Artisan command. Defaults to the `public` disk.
    |
    */

    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Define the supported locales for your application. When `localize_routes`
    | is enabled, locale-prefixed routes are registered (e.g. /de/llms.txt).
    |
    */

    'locales' => [],

    'localize_routes' => false,

];

<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use SchaeferSoft\LaravelLlmsTxt\Http\Controllers\LlmsTxtController;

/**
 * Handles registration of the llms.txt routes.
 *
 * Shared between LlmsTxtServiceProvider and LlmsTxt::routes() to avoid
 * duplication. Route registration is idempotent — calling register() when
 * the routes already exist is a no-op.
 */
class RouteRegistrar
{
    /**
     * Register the llms.txt and llms-full.txt routes.
     *
     * The llms-full.txt route is only registered when `full_route_enabled`
     * is true, since serving it dynamically triggers HTTP requests to every
     * entry URL.
     *
     * Each route is guarded by a check for its named-route existence, so
     * calling this method multiple times does not produce duplicate routes.
     *
     * When `localize_routes` is enabled in config, locale-prefixed variants
     * are also registered (e.g. `/{locale}/llms.txt`).
     */
    public static function register(): void
    {
        $router = app('router');
        $fullEnabled = (bool) config('llms-txt.full_route_enabled', false);

        if (! $router->has('llms-txt.index')) {
            $router->get(
                config('llms-txt.llms_txt_route', '/llms.txt'),
                [LlmsTxtController::class, 'index'],
            )->name('llms-txt.index');
        }

        if ($fullEnabled && ! $router->has('llms-txt.full')) {
            $router->get(
                config('llms-txt.llms_full_txt_route', '/llms-full.txt'),
                [LlmsTxtController::class, 'full'],
            )->name('llms-txt.full');
        }

        if (config('llms-txt.localize_routes', false)) {
            $locales = config('llms-txt.locales', []);

            if (! $router->has('llms-txt.localized.index')) {
                $router->get('/{locale}/llms.txt', [LlmsTxtController::class, 'localizedIndex'])
                    ->whereIn('locale', $locales)
                    ->name('llms-txt.localized.index');
            }

            if ($fullEnabled && ! $router->has('llms-txt.localized.full')) {
                $router->get('/{locale}/llms-full.txt', [LlmsTxtController::class, 'localizedFull'])
                    ->whereIn('locale', $locales)
                    ->name('llms-txt.localized.full');
            }
        }

        $router->getRoutes()->refreshNameLookups();
    }
}

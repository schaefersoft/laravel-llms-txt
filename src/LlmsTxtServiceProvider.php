<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Illuminate\Support\ServiceProvider;
use SchaeferSoft\LaravelLlmsTxt\Commands\GenerateLlmsTxtCommand;
use SchaeferSoft\LaravelLlmsTxt\Http\Controllers\LlmsTxtController;

/**
 * Service provider for the laravel-llms-txt package.
 *
 * Registers controller-based routes for serving llms.txt and llms-full.txt,
 * publishes the package configuration file, and registers the Artisan
 * command for static file generation.
 *
 * Routes are controller-based (not closures) so that `php artisan route:cache`
 * works without issues.
 */
class LlmsTxtServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services.
     *
     * Registers routes (when enabled via config) and makes the config
     * file publishable via `php artisan vendor:publish`.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/llms-txt.php' => config_path('llms-txt.php'),
        ], 'llms-txt-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateLlmsTxtCommand::class,
            ]);
        }

        if (config('llms-txt.route_enabled', true)) {
            $this->registerRoutes();
        }
    }

    /**
     * Register package bindings in the service container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/llms-txt.php',
            'llms-txt',
        );
    }

    /**
     * Register the dynamic routes for llms.txt and llms-full.txt.
     *
     * When `localize_routes` is enabled, locale-prefixed variants are
     * also registered (e.g. `/de/llms.txt`, `/en/llms.txt`).
     */
    protected function registerRoutes(): void
    {
        $router = $this->app['router'];

        $router->get(
            config('llms-txt.llms_txt_route', '/llms.txt'),
            [LlmsTxtController::class, 'index'],
        )->name('llms-txt.index');

        $router->get(
            config('llms-txt.llms_full_txt_route', '/llms-full.txt'),
            [LlmsTxtController::class, 'full'],
        )->name('llms-txt.full');

        if (config('llms-txt.localize_routes', false)) {
            $this->registerLocalizedRoutes($router);
        }
    }

    /**
     * Register locale-prefixed route variants using a single parameterised route.
     *
     * Registers:
     * - `/{locale}/llms.txt`
     * - `/{locale}/llms-full.txt`
     *
     * The `{locale}` segment is constrained to the values in `llms-txt.locales`,
     * so any unknown locale segment results in a 404.
     */
    protected function registerLocalizedRoutes(mixed $router): void
    {
        $locales = config('llms-txt.locales', []);

        $router->get('/{locale}/llms.txt', [LlmsTxtController::class, 'localizedIndex'])
            ->whereIn('locale', $locales)
            ->name('llms-txt.localized.index');

        $router->get('/{locale}/llms-full.txt', [LlmsTxtController::class, 'localizedFull'])
            ->whereIn('locale', $locales)
            ->name('llms-txt.localized.full');
    }
}

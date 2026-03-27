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

        if (config('llms-txt.route_enabled', true) && config('llms-txt.register_routes', true)) {
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
     * Delegates to RouteRegistrar::register() which is idempotent and shared
     * with LlmsTxt::routes() to avoid duplication.
     */
    protected function registerRoutes(): void
    {
        RouteRegistrar::register();
    }

    /**
     * Register locale-prefixed route variants using a single parameterised route.
     *
     * Kept for backwards compatibility with subclasses that may override this
     * method. Route registration now happens via RouteRegistrar::register().
     *
     * @deprecated Registration is handled by RouteRegistrar::register() internally.
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

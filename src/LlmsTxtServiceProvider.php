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
 * Routes are controller-based (never closure-based) so they are compatible
 * with `php artisan route:cache`.
 *
 * The controller reads app()->getLocale(), which is set by whatever
 * localization middleware the application uses (mcamara, spatie, custom, etc.)
 * before the request reaches the controller. No localization package
 * integration is needed.
 */
class LlmsTxtServiceProvider extends ServiceProvider
{
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

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/llms-txt.php',
            'llms-txt',
        );
    }

    /**
     * Register the default and (optionally) locale-prefixed routes.
     *
     * All routes point to controller actions so they survive route:cache.
     * When localize_routes is enabled, one `/{locale}/llms.txt` route is
     * registered per locale listed in `llms-txt.locales`, constrained via
     * whereIn so unknown locale segments fall through to a 404.
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
     * Register `/{locale}/llms.txt` and `/{locale}/llms-full.txt` routes.
     *
     * The {locale} segment is constrained to the values in `llms-txt.locales`.
     * The controller sets app()->setLocale() before rendering so that all
     * Closures in your LlmsTxt binding evaluate with the correct locale.
     * Unknown locale segments fall through to a 404.
     */
    protected function registerLocalizedRoutes(mixed $router): void
    {
        $locales = config('llms-txt.locales', []);

        if (empty($locales)) {
            return;
        }

        $router->get('/{locale}/llms.txt', [LlmsTxtController::class, 'localizedIndex'])
            ->whereIn('locale', $locales)
            ->name('llms-txt.localized.index');

        $router->get('/{locale}/llms-full.txt', [LlmsTxtController::class, 'localizedFull'])
            ->whereIn('locale', $locales)
            ->name('llms-txt.localized.full');
    }
}

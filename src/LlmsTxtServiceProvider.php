<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use SchaeferSoft\LaravelLlmsTxt\Commands\GenerateLlmsTxtCommand;

/**
 * Service provider for the laravel-llms-txt package.
 *
 * Registers dynamic routes for serving llms.txt and llms-full.txt,
 * publishes the package configuration file, and registers the Artisan
 * command for static file generation.
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

        $llmsRoute = config('llms-txt.llms_txt_route', '/llms.txt');
        $llmsFullRoute = config('llms-txt.llms_full_txt_route', '/llms-full.txt');

        $router->get($llmsRoute, function () {
            return $this->buildTextResponse($this->resolveLlmsTxt()->getCached('llms-txt'));
        })->name('llms-txt.index');

        $router->get($llmsFullRoute, function () {
            $llmsTxt = $this->resolveLlmsTxt();
            $content = $llmsTxt->renderFull();

            return $this->buildTextResponse($content);
        })->name('llms-txt.full');

        if (config('llms-txt.localize_routes', false)) {
            $this->registerLocalizedRoutes($router);
        }
    }

    /**
     * Register locale-prefixed route variants.
     *
     * For each locale defined in `llms-txt.locales`, registers:
     * - `/{locale}/llms.txt`
     * - `/{locale}/llms-full.txt`
     *
     * @param  Router  $router
     */
    protected function registerLocalizedRoutes(mixed $router): void
    {
        $locales = config('llms-txt.locales', []);

        foreach ($locales as $locale) {
            $router->get("/{$locale}/llms.txt", function () use ($locale) {
                $this->app->setLocale($locale);
                $llmsTxt = $this->resolveLlmsTxt();
                $content = $llmsTxt->getCached("llms-txt.{$locale}");

                return $this->buildTextResponse($content);
            })->name("llms-txt.{$locale}.index");

            $router->get("/{$locale}/llms-full.txt", function () use ($locale) {
                $this->app->setLocale($locale);
                $llmsTxt = $this->resolveLlmsTxt();
                $content = $llmsTxt->renderFull();

                return $this->buildTextResponse($content);
            })->name("llms-txt.{$locale}.full");
        }
    }

    /**
     * Resolve the LlmsTxt instance from the service container.
     *
     * If the application has bound a custom `LlmsTxt` instance, it is
     * used; otherwise a fresh empty instance is returned.
     */
    protected function resolveLlmsTxt(): LlmsTxt
    {
        $locale = $this->app->getLocale();

        if (LlmsTxtRegistry::hasLocale($locale)) {
            return LlmsTxtRegistry::resolve($locale);
        }

        if ($this->app->bound(LlmsTxt::class)) {
            return $this->app->make(LlmsTxt::class);
        }

        return new LlmsTxt;
    }

    /**
     * Build a plain-text HTTP response with the correct content type.
     *
     * @param  string  $content  The response body.
     */
    protected function buildTextResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}

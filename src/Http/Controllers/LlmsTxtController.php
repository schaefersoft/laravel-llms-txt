<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

/**
 * HTTP controller for serving llms.txt and llms-full.txt responses.
 *
 * Using a controller instead of route closures keeps routes serialisable,
 * which is required for `php artisan route:cache` to work correctly.
 */
class LlmsTxtController extends Controller
{
    /**
     * Serve the default llms.txt content.
     */
    public function index(): Response
    {
        return $this->textResponse(
            $this->resolve()->getCached('llms-txt.'.app()->getLocale())
        );
    }

    /**
     * Serve the default llms-full.txt content.
     */
    public function full(): Response
    {
        return $this->textResponse(
            $this->resolve()->getCachedFull('llms-txt-full.'.app()->getLocale())
        );
    }

    /**
     * Serve a locale-specific llms.txt content.
     *
     * Sets the application locale before resolving the instance so that
     * any `__()` calls inside the binding return the correct translation.
     */
    public function localizedIndex(string $locale): Response
    {
        app()->setLocale($locale);

        return $this->textResponse(
            $this->resolve()->getCached('llms-txt.'.app()->getLocale())
        );
    }

    /**
     * Serve a locale-specific llms-full.txt content.
     */
    public function localizedFull(string $locale): Response
    {
        app()->setLocale($locale);

        return $this->textResponse(
            $this->resolve()->getCachedFull('llms-txt-full.'.app()->getLocale())
        );
    }

    /**
     * Resolve the LlmsTxt instance from the service container.
     *
     * Uses the bound instance if one has been registered, otherwise
     * falls back to a fresh empty instance.
     */
    protected function resolve(): LlmsTxt
    {
        if (app()->bound(LlmsTxt::class)) {
            return app(LlmsTxt::class);
        }

        return new LlmsTxt;
    }

    /**
     * Build a plain-text HTTP response with the correct content type.
     */
    protected function textResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}

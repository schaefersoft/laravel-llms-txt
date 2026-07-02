<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Builds a LlmsTxt instance automatically from all registered GET routes.
 *
 * Used as a fallback when no manual LlmsTxt binding exists in the container.
 * The resulting document groups all GET routes into a single "Routes" section,
 * excluding the llms.txt routes themselves and clearly internal routes.
 *
 * @example
 * ```php
 * // In LlmsTxtController or GenerateLlmsTxtCommand fallback:
 * return AutoResolver::resolve();
 * ```
 */
class AutoResolver
{
    /**
     * Prefixes that identify clearly internal routes to be excluded.
     */
    private const INTERNAL_PREFIXES = ['_', 'telescope', 'horizon', 'debugbar'];

    /**
     * Build a LlmsTxt instance from all registered GET routes.
     *
     * Collects all GET routes, excludes the llms.txt routes themselves,
     * routes that are clearly internal, and routes matching the
     * `llms-txt.exclude_routes` config patterns, then maps each remaining
     * route into an Entry grouped under a single "Routes" section.
     *
     * The entry title is the route name when available, otherwise the URI.
     * The entry URL is generated via `url($route->uri())`.
     */
    public static function resolve(): LlmsTxt
    {
        $llmsUri = ltrim(config('llms-txt.llms_txt_route', '/llms.txt'), '/');
        $llmsFullUri = ltrim(config('llms-txt.llms_full_txt_route', '/llms-full.txt'), '/');

        $excludePatterns = array_map(
            fn (string $pattern): string => ltrim($pattern, '/'),
            (array) config('llms-txt.exclude_routes', []),
        );

        $section = Section::create('Routes');

        /** @var Route $route */
        foreach (app('router')->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (empty($uri)) {
                continue;
            }

            if ($uri === $llmsUri || $uri === $llmsFullUri) {
                continue;
            }

            if (str_contains($uri, '{')) {
                continue;
            }

            foreach (self::INTERNAL_PREFIXES as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    continue 2;
                }
            }

            if (self::isExcluded($route, $uri, $excludePatterns)) {
                continue;
            }

            $title = $route->getName() ?? $uri;

            $section->addEntry(
                Entry::create($title, url($uri), '')
            );
        }

        return LlmsTxt::create()
            ->title(config('app.name', 'Laravel'))
            ->addSection($section);
    }

    /**
     * Determine whether a route matches one of the configured exclude patterns.
     *
     * Patterns are matched against both the route URI (without leading slash)
     * and the route name, and support the `*` wildcard via Str::is().
     *
     * @param  list<string>  $patterns
     */
    private static function isExcluded(Route $route, string $uri, array $patterns): bool
    {
        if ($patterns === []) {
            return false;
        }

        if (Str::is($patterns, $uri)) {
            return true;
        }

        $name = $route->getName();

        return $name !== null && Str::is($patterns, $name);
    }
}

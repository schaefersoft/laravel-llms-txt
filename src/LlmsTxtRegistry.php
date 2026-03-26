<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Closure;

/**
 * Registry for locale-specific LlmsTxt factories.
 *
 * Allows registering a separate builder closure per locale so that
 * the correct content is served based on the application's current locale.
 *
 * @example
 * ```php
 * LlmsTxtRegistry::forLocale('de', fn() =>
 *     LlmsTxt::create()
 *         ->title('SchaeferSoft')
 *         ->addSection(
 *             Section::create('Leistungen')
 *                 ->addEntry(Entry::create('Webentwicklung', 'https://example.com/de/leistungen'))
 *         )
 * );
 *
 * LlmsTxtRegistry::forLocale('en', fn() =>
 *     LlmsTxt::create()
 *         ->title('SchaeferSoft')
 *         ->addSection(
 *             Section::create('Services')
 *                 ->addEntry(Entry::create('Web Development', 'https://example.com/en/services'))
 *         )
 * );
 * ```
 */
class LlmsTxtRegistry
{
    /**
     * @var array<string, Closure(): LlmsTxt>
     */
    protected static array $locales = [];

    /**
     * Register a factory closure for a given locale.
     *
     * @param  string  $locale  The locale identifier (e.g. 'de', 'en').
     * @param  Closure(): LlmsTxt  $factory  A closure returning a configured LlmsTxt instance.
     */
    public static function forLocale(string $locale, Closure $factory): void
    {
        static::$locales[$locale] = $factory;
    }

    /**
     * Resolve the LlmsTxt instance for the given locale.
     *
     * Returns null when no factory has been registered for that locale.
     */
    public static function resolve(string $locale): ?LlmsTxt
    {
        if (isset(static::$locales[$locale])) {
            return (static::$locales[$locale])();
        }

        return null;
    }

    /**
     * Check whether a factory is registered for the given locale.
     */
    public static function hasLocale(string $locale): bool
    {
        return isset(static::$locales[$locale]);
    }

    /**
     * Check whether any locale factories have been registered.
     */
    public static function hasAny(): bool
    {
        return ! empty(static::$locales);
    }

    /**
     * Return all registered locale identifiers.
     *
     * @return list<string>
     */
    public static function locales(): array
    {
        return array_keys(static::$locales);
    }

    /**
     * Clear all registered factories (primarily useful in tests).
     */
    public static function flush(): void
    {
        static::$locales = [];
    }
}

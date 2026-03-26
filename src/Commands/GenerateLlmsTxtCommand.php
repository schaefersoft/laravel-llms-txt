<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt\Commands;

use Illuminate\Console\Command;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

/**
 * Artisan command to generate static llms.txt and llms-full.txt files.
 *
 * When generating for a specific locale, the application locale is set via
 * app()->setLocale() before rendering so that Closure-based title/description/
 * entry values (e.g. fn() => __('llms.title')) resolve to the correct language.
 *
 * @example
 * ```bash
 * php artisan llms:generate
 * php artisan llms:generate --full
 * php artisan llms:generate --locale=de
 * php artisan llms:generate --locale=de --full
 * php artisan llms:generate --all-locales --full
 * ```
 */
class GenerateLlmsTxtCommand extends Command
{
    protected $signature = 'llms:generate
                            {--full : Also generate llms-full.txt by fetching each entry URL}
                            {--locale= : Generate for a specific locale (e.g. de, en)}
                            {--all-locales : Generate files for all configured locales}';

    protected $description = 'Generate static llms.txt (and optionally llms-full.txt) files';

    public function handle(): int
    {
        if ($this->option('all-locales')) {
            return $this->generateForAllLocales();
        }

        $locale = $this->option('locale');

        return $this->generateForLocale(is_string($locale) ? $locale : null);
    }

    protected function generateForAllLocales(): int
    {
        $locales = config('llms-txt.locales', []);

        if (empty($locales)) {
            $this->warn('No locales configured in llms-txt.locales. Generating default files instead.');

            return $this->generateForLocale(null);
        }

        $exitCode = self::SUCCESS;

        foreach ($locales as $locale) {
            $result = $this->generateForLocale($locale);

            if ($result !== self::SUCCESS) {
                $exitCode = $result;
            }
        }

        return $exitCode;
    }

    /**
     * Generate llms.txt (and optionally llms-full.txt) for the given locale.
     *
     * Sets app()->setLocale() before resolving and rendering so that any
     * Closure-based values in the bound LlmsTxt instance evaluate correctly.
     */
    protected function generateForLocale(?string $locale): int
    {
        if ($locale !== null) {
            app()->setLocale($locale);
        }

        $llmsTxt = $this->resolve();

        if ($locale !== null) {
            $llmsTxt->locale($locale);
        }

        $localeLabel = $locale ?? 'default';

        $this->info("Generating llms.txt for locale: {$localeLabel}");

        $written = $llmsTxt->writeToDisk();

        if (! $written) {
            $this->error("Failed to write llms.txt for locale: {$localeLabel}");

            return self::FAILURE;
        }

        $disk = config('llms-txt.disk', 'public');
        $filename = $locale ? "{$locale}/llms.txt" : 'llms.txt';
        $this->line("  <info>✔</info> Written to [{$disk}] {$filename}");

        if ($this->option('full')) {
            $this->info("Generating llms-full.txt for locale: {$localeLabel}");

            $writtenFull = $llmsTxt->writeFullToDisk();

            if (! $writtenFull) {
                $this->error("Failed to write llms-full.txt for locale: {$localeLabel}");

                return self::FAILURE;
            }

            $fullFilename = $locale ? "{$locale}/llms-full.txt" : 'llms-full.txt';
            $this->line("  <info>✔</info> Written to [{$disk}] {$fullFilename}");
        }

        $this->newLine();

        return self::SUCCESS;
    }

    protected function resolve(): LlmsTxt
    {
        if (app()->bound(LlmsTxt::class)) {
            return app(LlmsTxt::class);
        }

        return new LlmsTxt;
    }
}

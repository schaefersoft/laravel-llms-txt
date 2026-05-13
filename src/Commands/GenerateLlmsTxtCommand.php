<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt\Commands;

use Illuminate\Console\Command;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

/**
 * Artisan command to generate static llms.txt and llms-full.txt files.
 *
 * Writes the rendered output to the configured filesystem disk (default: public).
 * Supports an optional `--full` flag to also generate the extended llms-full.txt,
 * and a `--locale` option to generate locale-specific files.
 *
 * When a locale is given, `app()->setLocale()` is called before resolving the
 * LlmsTxt instance so that any Closures in title/description/section names are
 * evaluated with the correct locale active.
 *
 * @example
 * ```bash
 * php artisan llms:generate
 * php artisan llms:generate --full
 * php artisan llms:generate --locale=de
 * php artisan llms:generate --locale=de --full
 * ```
 */
class GenerateLlmsTxtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llms:generate
                            {--full : Also generate llms-full.txt by fetching each entry URL}
                            {--locale= : Generate for a specific locale (e.g. de, en)}
                            {--all-locales : Generate files for all configured locales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate static llms.txt (and optionally llms-full.txt) files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all-locales')) {
            return $this->generateForAllLocales();
        }

        $locale = $this->option('locale');

        return $this->generateForLocale(is_string($locale) ? $locale : null);
    }

    /**
     * Generate files for all locales defined in config.
     */
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
     * Sets the application locale before resolving the LlmsTxt instance so that
     * Closure-based titles and descriptions are translated correctly.
     *
     * @param  string|null  $locale  The locale to generate for, or null for the default.
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

    /**
     * Resolve the LlmsTxt instance via the registered configure() callback,
     * or fall back to AutoResolver when none is set.
     */
    protected function resolve(): LlmsTxt
    {
        return LlmsTxt::resolve();
    }
}

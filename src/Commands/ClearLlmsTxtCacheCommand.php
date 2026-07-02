<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt\Commands;

use Illuminate\Console\Command;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

/**
 * Artisan command to flush all cached llms.txt output.
 *
 * Clears the base cache keys (`llms-txt`, `llms-txt-full`) as well as the
 * locale-suffixed variants used by the HTTP controller (e.g. `llms-txt.de`)
 * for every configured locale, the app locale, and the fallback locale.
 *
 * @example
 * ```bash
 * php artisan llms:clear
 * ```
 */
class ClearLlmsTxtCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llms:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all cached llms.txt and llms-full.txt output';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        LlmsTxt::make()->flushCache();

        $this->info('llms.txt cache cleared.');

        return self::SUCCESS;
    }
}

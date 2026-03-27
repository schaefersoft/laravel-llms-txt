<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;

/**
 * Main builder class for constructing and rendering an llms.txt document.
 *
 * Provides a fluent, chainable API for defining the site title, description,
 * locale, and sections with entries. Supports writing to disk, retrieving
 * cached output, and generating the extended llms-full.txt format.
 *
 * Title and description accept either a plain string or a Closure that returns
 * a string. Closures are evaluated lazily at render time, which means they
 * respect the application's current locale — ideal for use with `__()`.
 *
 * @example
 * ```php
 * LlmsTxt::create()
 *     ->title(fn () => __('llms.title'))
 *     ->description(fn () => __('llms.description'))
 *     ->addSection(
 *         Section::create(fn () => __('llms.services'))
 *             ->addEntry(Entry::create(
 *                 fn () => __('llms.web_dev'),
 *                 'https://example.com/services',
 *             ))
 *     )
 *     ->writeToDisk();
 * ```
 */
class LlmsTxt
{
    /**
     * The site title rendered as a top-level heading.
     */
    protected string|Closure $title = '';

    /**
     * The site tagline or description rendered as a blockquote.
     */
    protected string|Closure|null $description = null;

    /**
     * The locale for this document (used only for file path resolution).
     */
    protected ?string $locale = null;

    /**
     * The ordered collection of sections in this document.
     *
     * @var Collection<int, Section>
     */
    protected Collection $sections;

    /**
     * Create a new LlmsTxt instance.
     */
    public function __construct()
    {
        $this->sections = new Collection;
    }

    /**
     * Static factory method for fluent construction.
     */
    public static function create(): static
    {
        return new static;
    }

    /**
     * Static factory method — alias for create().
     *
     * Both create() and make() remain and behave identically.
     *
     * @example
     * ```php
     * LlmsTxt::make()->title('My Site')->render();
     * ```
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Set the site title.
     */
    public function title(string|Closure $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the site description / tagline.
     */
    public function description(string|Closure $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the locale for this document (used for file path resolution only).
     */
    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Add a section to the document.
     */
    public function addSection(Section $section): static
    {
        $this->sections->push($section);

        return $this;
    }

    /**
     * Create a section inline and add it to the document via a closure.
     *
     * The closure receives the new Section instance and should configure it
     * (e.g. add entries). The Section is pushed to the document and $this
     * is returned for chaining.
     *
     * @example
     * ```php
     * LlmsTxt::make()
     *     ->section('Services', fn ($s) => $s
     *         ->entry('Web Dev', 'https://example.com/web', 'Laravel & Vue.js')
     *         ->entry('Hosting', 'https://example.com/hosting')
     *     );
     * ```
     */
    public function section(string|Closure $title, Closure $callback): static
    {
        $section = Section::create($title);
        $callback($section);
        $this->sections->push($section);

        return $this;
    }

    /**
     * Conditionally apply a callback to this document.
     *
     * Mirrors Laravel's own when() behaviour: if $condition is truthy (or a
     * Closure that returns truthy), $callback is invoked with $this as its
     * argument. Always returns $this for chaining.
     *
     * @example
     * ```php
     * LlmsTxt::make()
     *     ->section('Services', fn ($s) => $s->entry('Web Dev', 'https://...'))
     *     ->when(config('features.api'), fn ($llms) => $llms
     *         ->section('API', fn ($s) => $s->entry('Docs', 'https://...'))
     *     );
     * ```
     */
    public function when(bool|Closure $condition, Closure $callback): static
    {
        $result = $condition instanceof Closure ? ($condition)() : $condition;

        if ($result) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Register the llms.txt routes on demand.
     *
     * Respects the `route_enabled`, `llms_txt_route`, `llms_full_txt_route`,
     * and `localize_routes` config values. Safe to call multiple times —
     * routes are only registered once (idempotent).
     *
     * Useful when `register_routes => false` in config and you want to place
     * the routes inside a specific middleware group in routes/web.php.
     *
     * @example
     * ```php
     * // routes/web.php
     * Route::middleware(['web', 'cache.headers:public;max_age=3600'])
     *     ->group(function () {
     *         LlmsTxt::routes();
     *     });
     * ```
     */
    public static function routes(): void
    {
        RouteRegistrar::register();
    }

    /**
     * Get the site title, evaluating any Closure.
     */
    public function getTitle(): string
    {
        return $this->resolveValue($this->title);
    }

    /**
     * Get the description, evaluating any Closure.
     */
    public function getDescription(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        return $this->resolveValue($this->description);
    }

    /**
     * Get the locale.
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Get all sections.
     *
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    /**
     * Render the document as an llms.txt string.
     *
     * The output follows the llms.txt specification:
     * - A top-level `# Title` heading
     * - An optional `> Description` blockquote
     * - One or more `## Section` headings, each containing link entries
     */
    public function render(): string
    {
        $parts = [];

        $title = $this->resolveValue($this->title);

        if ($title !== '') {
            $parts[] = "# {$title}";
        }

        if ($this->description !== null) {
            $description = $this->resolveValue($this->description);

            if ($description !== '') {
                $parts[] = "> {$description}";
            }
        }

        foreach ($this->sections as $section) {
            $parts[] = $section->render();
        }

        return implode("\n\n", $parts)."\n";
    }

    /**
     * Render the document as an llms-full.txt string.
     *
     * Same structure as llms.txt but fetches the remote content of each
     * entry URL and appends it below the entry line. Fetched content is
     * included verbatim inside a fenced code block.
     *
     * @param  Client|null  $httpClient  Optional Guzzle client for testing.
     */
    public function renderFull(?Client $httpClient = null): string
    {
        $client = $httpClient ?? new Client(['timeout' => 10, 'http_errors' => false]);

        $parts = [];

        $title = $this->resolveValue($this->title);

        if ($title !== '') {
            $parts[] = "# {$title}";
        }

        if ($this->description !== null) {
            $description = $this->resolveValue($this->description);

            if ($description !== '') {
                $parts[] = "> {$description}";
            }
        }

        foreach ($this->sections as $section) {
            $sectionParts = ['## '.$section->getName()];

            foreach ($section->getEntries() as $entry) {
                $sectionParts[] = $entry->render();

                $content = $this->fetchEntryContent($client, $entry->getUrl());

                if ($content !== null) {
                    $sectionParts[] = "\n".$content;
                }
            }

            $parts[] = implode("\n", $sectionParts);
        }

        return implode("\n\n", $parts)."\n";
    }

    /**
     * Fetch the content of an entry URL.
     *
     * Returns null when the request fails or the response is not successful.
     *
     * @param  Client  $client  The Guzzle HTTP client.
     * @param  string  $url  The URL to fetch.
     */
    protected function fetchEntryContent(Client $client, string $url): ?string
    {
        try {
            $response = $client->get($url);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return trim((string) $response->getBody());
            }
        } catch (GuzzleException) {
            // Silently skip entries that cannot be fetched.
        }

        return null;
    }

    /**
     * Write the llms.txt output to the configured filesystem disk.
     *
     * The filename is derived from the locale when one is set, so
     * `->locale('de')->writeToDisk()` writes to `de/llms.txt`.
     *
     * @param  string|null  $filename  Override the output filename.
     * @return bool `true` on success.
     */
    public function writeToDisk(?string $filename = null): bool
    {
        $disk = $this->getFilesystemManager()->disk(config('llms-txt.disk', 'public'));

        $path = $filename ?? $this->resolveFilename('llms.txt');

        return $disk->put($path, $this->render());
    }

    /**
     * Write the llms-full.txt output to the configured filesystem disk.
     *
     * @param  string|null  $filename  Override the output filename.
     * @param  Client|null  $httpClient  Optional Guzzle client for testing.
     * @return bool `true` on success.
     */
    public function writeFullToDisk(?string $filename = null, ?Client $httpClient = null): bool
    {
        $disk = $this->getFilesystemManager()->disk(config('llms-txt.disk', 'public'));

        $path = $filename ?? $this->resolveFilename('llms-full.txt');

        return $disk->put($path, $this->renderFull($httpClient));
    }

    /**
     * Get a cached version of the rendered output, or render and cache it.
     *
     * Caching is controlled by the `llms-txt.cache_enabled` and
     * `llms-txt.cache_ttl` configuration values.
     *
     * @param  string  $key  The cache key to store the output under.
     */
    public function getCached(string $key = 'llms-txt'): string
    {
        if (! config('llms-txt.cache_enabled', false)) {
            return $this->render();
        }

        /** @var CacheRepository $cache */
        $cache = app('cache');
        $ttl = (int) config('llms-txt.cache_ttl', 3600);

        return $cache->remember($key, $ttl, fn () => $this->render());
    }

    /**
     * Get a cached version of the llms-full.txt output, or render and cache it.
     *
     * Same caching behaviour as getCached() but calls renderFull() — avoiding
     * repeated HTTP requests to every entry URL on each request.
     *
     * @param  string  $key  The cache key to store the output under.
     * @param  Client|null  $httpClient  Optional Guzzle client for testing.
     */
    public function getCachedFull(string $key = 'llms-txt-full', ?Client $httpClient = null): string
    {
        if (! config('llms-txt.cache_enabled', false)) {
            return $this->renderFull($httpClient);
        }

        /** @var CacheRepository $cache */
        $cache = app('cache');
        $ttl = (int) config('llms-txt.cache_ttl', 3600);

        return $cache->remember($key, $ttl, fn () => $this->renderFull($httpClient));
    }

    /**
     * Flush a previously cached output.
     *
     * @param  string  $key  The cache key to flush.
     */
    public function flushCache(string $key = 'llms-txt'): bool
    {
        /** @var CacheRepository $cache */
        $cache = app('cache');

        return $cache->forget($key);
    }

    /**
     * Resolve the storage filename, optionally prefixing with locale.
     *
     * @param  string  $basename  The base filename (e.g. 'llms.txt').
     */
    protected function resolveFilename(string $basename): string
    {
        if ($this->locale !== null && $this->locale !== '') {
            return "{$this->locale}/{$basename}";
        }

        return $basename;
    }

    /**
     * Resolve the filesystem manager from the container.
     */
    protected function getFilesystemManager(): FilesystemManager
    {
        return app(FilesystemManager::class);
    }

    /**
     * Return the rendered string representation of this document.
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Resolve a value that may be a plain string or a Closure.
     */
    private function resolveValue(string|Closure $value): string
    {
        return $value instanceof Closure ? ($value)() : $value;
    }
}

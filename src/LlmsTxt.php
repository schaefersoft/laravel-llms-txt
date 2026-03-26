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
 * Title and description accept either a plain string or a Closure. Closures
 * are evaluated lazily at render time, so they pick up whatever locale
 * `app()->setLocale()` has set by then — enabling seamless use of Laravel's
 * `__()` / `trans()` helpers for multilingual output.
 *
 * @example Plain strings
 * ```php
 * LlmsTxt::create()
 *     ->title('SchaeferSoft')
 *     ->description('Web development and software agency')
 *     ->addSection(
 *         Section::create('Services')
 *             ->addEntry(Entry::create('Web Development', 'https://example.com/services'))
 *     )
 *     ->writeToDisk();
 * ```
 *
 * @example Translated via lang files
 * ```php
 * LlmsTxt::create()
 *     ->title(fn() => __('llms.title'))
 *     ->description(fn() => __('llms.description'))
 *     ->addSection(
 *         Section::create(fn() => __('llms.sections.services'))
 *             ->addEntry(Entry::create(
 *                 fn() => __('llms.entries.web.title'),
 *                 'https://example.com/services/web',
 *                 fn() => __('llms.entries.web.description'),
 *             ))
 *     );
 * ```
 */
class LlmsTxt
{
    /**
     * The site title rendered as a top-level heading (string or lazy Closure).
     *
     * @var string|Closure(): string
     */
    protected string|Closure $title = '';

    /**
     * The site tagline or description rendered as a blockquote (string, lazy Closure, or null).
     *
     * @var string|Closure(): string|null
     */
    protected string|Closure|null $description = null;

    /**
     * The locale used for file path resolution (e.g. 'de', 'en').
     *
     * This is only used to determine the output path when calling writeToDisk().
     * Content locale is controlled by app()->setLocale() before render.
     */
    protected ?string $locale = null;

    /**
     * The ordered collection of sections in this document.
     *
     * @var Collection<int, Section>
     */
    protected Collection $sections;

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
     * Set the site title.
     *
     * @param  string|Closure(): string  $title
     */
    public function title(string|Closure $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the site description / tagline.
     *
     * @param  string|Closure(): string  $description
     */
    public function description(string|Closure $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the locale used for writeToDisk() path resolution.
     *
     * This does NOT affect which content is rendered — set the application
     * locale via app()->setLocale() before calling render() for that.
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
     * Get the resolved site title.
     */
    public function getTitle(): string
    {
        return $this->resolveValue($this->title);
    }

    /**
     * Get the resolved description.
     */
    public function getDescription(): ?string
    {
        return $this->description !== null ? $this->resolveValue($this->description) : null;
    }

    /**
     * Get the locale used for file path resolution.
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
     * All Closures (title, description, section names, entry fields) are
     * evaluated here, after app()->setLocale() has been called by the caller.
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
     * entry URL and appends it below the entry line.
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
     * @param  string|null  $filename    Override the output filename.
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
     * Resolve the storage filename, optionally prefixing with the locale.
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
     * Evaluate a value, calling it if it is a Closure.
     *
     * @param  string|Closure(): string  $value
     */
    private function resolveValue(string|Closure $value): string
    {
        return $value instanceof Closure ? ($value)() : $value;
    }
}

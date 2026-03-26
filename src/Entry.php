<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Closure;

/**
 * Represents a single link entry within a section of an llms.txt document.
 *
 * Title, URL, and description accept either a plain string or a Closure.
 * Closures are evaluated lazily at render time, so they pick up whatever
 * locale `app()->setLocale()` has set by then — enabling seamless use of
 * Laravel's `__()` / `trans()` helpers for multilingual output.
 *
 * @example Plain strings
 * ```php
 * Entry::create('Web Development', 'https://example.com/services', 'Modern web apps')
 * ```
 *
 * @example Translated via lang files
 * ```php
 * Entry::create(
 *     fn() => __('llms.entries.web.title'),
 *     'https://example.com/services/web',
 *     fn() => __('llms.entries.web.description'),
 * )
 * ```
 */
class Entry
{
    /**
     * The display title of the entry (string or lazy Closure).
     *
     * @var string|Closure(): string
     */
    protected string|Closure $title;

    /**
     * The URL this entry points to (string or lazy Closure).
     *
     * @var string|Closure(): string
     */
    protected string|Closure $url;

    /**
     * An optional description (string, lazy Closure, or null).
     *
     * @var string|Closure(): string|null
     */
    protected string|Closure|null $description;

    /**
     * The locale associated with this entry (e.g. 'de', 'en').
     */
    protected ?string $locale;

    /**
     * @param  string|Closure(): string       $title        The display title.
     * @param  string|Closure(): string       $url          The URL.
     * @param  string|Closure(): string|null  $description  An optional description.
     * @param  string|null                    $locale       An optional locale identifier.
     */
    public function __construct(
        string|Closure $title,
        string|Closure $url,
        string|Closure|null $description = null,
        ?string $locale = null,
    ) {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->locale = $locale;
    }

    /**
     * Static factory method for fluent construction.
     *
     * @param  string|Closure(): string       $title        The display title.
     * @param  string|Closure(): string       $url          The URL.
     * @param  string|Closure(): string|null  $description  An optional description.
     * @param  string|null                    $locale       An optional locale identifier.
     */
    public static function create(
        string|Closure $title,
        string|Closure $url,
        string|Closure|null $description = null,
        ?string $locale = null,
    ): static {
        return new static($title, $url, $description, $locale);
    }

    /**
     * Set the display title.
     *
     * @param  string|Closure(): string  $title
     */
    public function title(string|Closure $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the URL.
     *
     * @param  string|Closure(): string  $url
     */
    public function url(string|Closure $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the description.
     *
     * @param  string|Closure(): string|null  $description
     */
    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the locale for this entry.
     */
    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the resolved display title.
     */
    public function getTitle(): string
    {
        return $this->resolveValue($this->title);
    }

    /**
     * Get the resolved URL.
     */
    public function getUrl(): string
    {
        return $this->resolveValue($this->url);
    }

    /**
     * Get the resolved description.
     */
    public function getDescription(): ?string
    {
        return $this->description !== null ? $this->resolveValue($this->description) : null;
    }

    /**
     * Get the locale.
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Render this entry as a single line of llms.txt markdown.
     *
     * Closures are evaluated here, after the application locale has been set.
     *
     * Format: `- [Title](https://url.com): Description`
     */
    public function render(): string
    {
        $title = $this->resolveValue($this->title);
        $url = $this->resolveValue($this->url);
        $description = $this->description !== null ? $this->resolveValue($this->description) : null;

        $line = "- [{$title}]({$url})";

        if ($description !== null && $description !== '') {
            $line .= ": {$description}";
        }

        return $line;
    }

    /**
     * Return the rendered string representation of this entry.
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

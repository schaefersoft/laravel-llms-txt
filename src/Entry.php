<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Closure;

/**
 * Represents a single link entry within a section of an llms.txt document.
 *
 * An entry consists of a title, a URL, and an optional description.
 * All three accept either a plain string or a Closure that returns a string.
 * Closures are evaluated lazily at render time — see the Advanced section of
 * the README for when this is useful.
 */
class Entry
{
    /**
     * The display title of the entry.
     */
    protected string|Closure $title;

    /**
     * The URL this entry points to.
     */
    protected string|Closure $url;

    /**
     * An optional description for the entry.
     */
    protected string|Closure|null $description;

    /**
     * Create a new Entry instance.
     *
     * @param  string|Closure  $title  The display title of the entry.
     * @param  string|Closure  $url  The URL this entry points to.
     * @param  string|Closure|null  $description  An optional description.
     */
    public function __construct(
        string|Closure $title,
        string|Closure $url,
        string|Closure|null $description = null,
    ) {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
    }

    /**
     * Static factory method for fluent construction.
     *
     * @param  string|Closure  $title  The display title of the entry.
     * @param  string|Closure  $url  The URL this entry points to.
     * @param  string|Closure|null  $description  An optional description.
     */
    public static function create(
        string|Closure $title,
        string|Closure $url,
        string|Closure|null $description = null,
    ): static {
        return new static($title, $url, $description);
    }

    /**
     * Set the display title.
     */
    public function title(string|Closure $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the URL.
     */
    public function url(string|Closure $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the description.
     */
    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the display title, evaluating any Closure.
     */
    public function getTitle(): string
    {
        return $this->resolveValue($this->title);
    }

    /**
     * Get the URL, evaluating any Closure.
     */
    public function getUrl(): string
    {
        return $this->resolveValue($this->url);
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
     * Render this entry as a single line of llms.txt markdown.
     *
     * Format: `- [Title](https://url.com): Description`
     */
    public function render(): string
    {
        $title = $this->resolveValue($this->title);
        $url = $this->resolveValue($this->url);

        $line = "- [{$title}]({$url})";

        if ($this->description !== null) {
            $description = $this->resolveValue($this->description);

            if ($description !== '') {
                $line .= ": {$description}";
            }
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
     * Resolve a value that may be a plain string or a Closure.
     */
    private function resolveValue(string|Closure $value): string
    {
        return $value instanceof Closure ? ($value)() : $value;
    }
}

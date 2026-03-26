<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

/**
 * Represents a single link entry within a section of an llms.txt document.
 *
 * An entry consists of a title, a URL, an optional description, and an
 * optional locale for multilingual site support.
 */
class Entry
{
    /**
     * The display title of the entry.
     */
    protected string $title;

    /**
     * The URL this entry points to.
     */
    protected string $url;

    /**
     * An optional description for the entry.
     */
    protected ?string $description;

    /**
     * The locale associated with this entry (e.g. 'de', 'en').
     */
    protected ?string $locale;

    /**
     * Create a new Entry instance.
     *
     * @param  string  $title  The display title of the entry.
     * @param  string  $url  The URL this entry points to.
     * @param  string|null  $description  An optional description.
     * @param  string|null  $locale  An optional locale identifier.
     */
    public function __construct(
        string $title,
        string $url,
        ?string $description = null,
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
     * @param  string  $title  The display title of the entry.
     * @param  string  $url  The URL this entry points to.
     * @param  string|null  $description  An optional description.
     * @param  string|null  $locale  An optional locale identifier.
     */
    public static function create(
        string $title,
        string $url,
        ?string $description = null,
        ?string $locale = null,
    ): static {
        return new static($title, $url, $description, $locale);
    }

    /**
     * Set the display title.
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the URL.
     */
    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the description.
     */
    public function description(?string $description): static
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
     * Get the display title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the URL.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
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
     * Format: `- [Title](https://url.com): Description`
     */
    public function render(): string
    {
        $line = "- [{$this->title}]({$this->url})";

        if ($this->description !== null && $this->description !== '') {
            $line .= ": {$this->description}";
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
}

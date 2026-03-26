<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Illuminate\Support\Collection;

/**
 * Represents a named section within an llms.txt document.
 *
 * A section groups related entries under a heading and can optionally
 * carry a locale for multilingual site support.
 */
class Section
{
    /**
     * The section heading.
     */
    protected string $name;

    /**
     * The locale associated with this section (e.g. 'de', 'en').
     */
    protected ?string $locale;

    /**
     * The collection of entries belonging to this section.
     *
     * @var Collection<int, Entry>
     */
    protected Collection $entries;

    /**
     * Create a new Section instance.
     *
     * @param  string  $name  The section heading.
     * @param  string|null  $locale  An optional locale identifier.
     */
    public function __construct(string $name, ?string $locale = null)
    {
        $this->name = $name;
        $this->locale = $locale;
        $this->entries = new Collection;
    }

    /**
     * Static factory method for fluent construction.
     *
     * @param  string  $name  The section heading.
     * @param  string|null  $locale  An optional locale identifier.
     */
    public static function create(string $name, ?string $locale = null): static
    {
        return new static($name, $locale);
    }

    /**
     * Set the section name.
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the locale for this section.
     */
    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Add an entry to this section.
     */
    public function addEntry(Entry $entry): static
    {
        $this->entries->push($entry);

        return $this;
    }

    /**
     * Get the section name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the locale.
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Get all entries in this section.
     *
     * @return Collection<int, Entry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    /**
     * Render this section as llms.txt markdown.
     *
     * Format:
     * ```
     * ## Section Name
     * - [Entry Title](https://url.com): Entry description
     * ```
     */
    public function render(): string
    {
        $lines = ["## {$this->name}"];

        foreach ($this->entries as $entry) {
            $lines[] = $entry->render();
        }

        return implode("\n", $lines);
    }

    /**
     * Return the rendered string representation of this section.
     */
    public function __toString(): string
    {
        return $this->render();
    }
}

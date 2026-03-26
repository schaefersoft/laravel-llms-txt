<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Closure;
use Illuminate\Support\Collection;

/**
 * Represents a named section within an llms.txt document.
 *
 * The section name accepts either a plain string or a Closure, evaluated
 * lazily at render time so that `__()` translations work correctly when
 * the application locale has already been set.
 *
 * @example Plain string
 * ```php
 * Section::create('Services')
 * ```
 *
 * @example Translated via lang files
 * ```php
 * Section::create(fn() => __('llms.sections.services'))
 * ```
 */
class Section
{
    /**
     * The section heading (string or lazy Closure).
     *
     * @var string|Closure(): string
     */
    protected string|Closure $name;

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
     * @param  string|Closure(): string  $name    The section heading.
     * @param  string|null               $locale  An optional locale identifier.
     */
    public function __construct(string|Closure $name, ?string $locale = null)
    {
        $this->name = $name;
        $this->locale = $locale;
        $this->entries = new Collection;
    }

    /**
     * Static factory method for fluent construction.
     *
     * @param  string|Closure(): string  $name    The section heading.
     * @param  string|null               $locale  An optional locale identifier.
     */
    public static function create(string|Closure $name, ?string $locale = null): static
    {
        return new static($name, $locale);
    }

    /**
     * Set the section name.
     *
     * @param  string|Closure(): string  $name
     */
    public function name(string|Closure $name): static
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
     * Get the resolved section name.
     */
    public function getName(): string
    {
        return $this->resolveValue($this->name);
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
     * Closures are evaluated here, after the application locale has been set.
     */
    public function render(): string
    {
        $lines = ['## '.$this->resolveValue($this->name)];

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

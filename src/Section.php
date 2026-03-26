<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt;

use Closure;
use Illuminate\Support\Collection;

/**
 * Represents a named section within an llms.txt document.
 *
 * A section groups related entries under a heading. The name accepts either a
 * plain string or a Closure that returns a string. Closures are evaluated
 * lazily at render time — see the Advanced section of the README for when
 * this is useful.
 */
class Section
{
    /**
     * The section heading.
     */
    protected string|Closure $name;

    /**
     * The collection of entries belonging to this section.
     *
     * @var Collection<int, Entry>
     */
    protected Collection $entries;

    /**
     * Create a new Section instance.
     *
     * @param  string|Closure  $name  The section heading.
     */
    public function __construct(string|Closure $name)
    {
        $this->name = $name;
        $this->entries = new Collection;
    }

    /**
     * Static factory method for fluent construction.
     *
     * @param  string|Closure  $name  The section heading.
     */
    public static function create(string|Closure $name): static
    {
        return new static($name);
    }

    /**
     * Set the section name.
     */
    public function name(string|Closure $name): static
    {
        $this->name = $name;

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
     * Get the section name, evaluating any Closure.
     */
    public function getName(): string
    {
        return $this->resolveValue($this->name);
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
     * Resolve a value that may be a plain string or a Closure.
     */
    private function resolveValue(string|Closure $value): string
    {
        return $value instanceof Closure ? ($value)() : $value;
    }
}

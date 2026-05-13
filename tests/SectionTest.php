<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\Section;

it('creates a section with a name', function () {
    $section = Section::create('Services');

    expect($section->getName())->toBe('Services')
        ->and($section->getEntries())->toHaveCount(0);
});

it('adds entries to a section', function () {
    $section = Section::create('Services')
        ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web'))
        ->addEntry(Entry::create('Hosting', 'https://schaefersoft.ch/services/hosting'));

    expect($section->getEntries())->toHaveCount(2);
});

it('renders a section with entries', function () {
    $section = Section::create('Services')
        ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web', 'Modern web apps'))
        ->addEntry(Entry::create('Hosting', 'https://schaefersoft.ch/services/hosting', 'Managed hosting'));

    $expected = implode("\n", [
        '## Services',
        '- [Web Development](https://schaefersoft.ch/services/web): Modern web apps',
        '- [Hosting](https://schaefersoft.ch/services/hosting): Managed hosting',
    ]);

    expect($section->render())->toBe($expected);
});

it('renders an empty section', function () {
    $section = Section::create('Empty');

    expect($section->render())->toBe('## Empty');
});

it('renders as string via __toString', function () {
    $section = Section::create('Services')
        ->addEntry(Entry::create('Web', 'https://schaefersoft.ch'));

    expect((string) $section)->toContain('## Services');
});

it('supports fluent name setter', function () {
    $section = Section::create('Old Name')->name('New Name');

    expect($section->getName())->toBe('New Name');
});

it('accepts a closure for the section name', function () {
    $section = Section::create(fn () => 'Dynamic Services');

    expect($section->getName())->toBe('Dynamic Services')
        ->and($section->render())->toBe('## Dynamic Services');
});

it('evaluates name closure at render time', function () {
    $locale = 'en';

    $section = Section::create(function () use (&$locale) {
        return $locale === 'de' ? 'Leistungen' : 'Services';
    });

    expect($section->render())->toContain('## Services');

    $locale = 'de';

    expect($section->render())->toContain('## Leistungen');
});

it('getName evaluates closures', function () {
    $section = Section::create(fn () => 'Computed Name');

    expect($section->getName())->toBe('Computed Name');
});

it('fluent name setter accepts a closure', function () {
    $section = Section::create('Old')->name(fn () => 'New Name From Closure');

    expect($section->getName())->toBe('New Name From Closure');
});

// ---------------------------------------------------------------------------
// Section::entries()
// ---------------------------------------------------------------------------

it('entries() maps items to entries via callback', function () {
    $items = [
        ['name' => 'Web Dev', 'url' => 'https://example.com/web'],
        ['name' => 'Hosting', 'url' => 'https://example.com/hosting'],
    ];

    $section = Section::create('Services')
        ->entries($items, fn ($item) => Entry::create($item['name'], $item['url']));

    expect($section->getEntries())->toHaveCount(2);
    expect($section->getEntries()->first()->getTitle())->toBe('Web Dev');
    expect($section->getEntries()->last()->getTitle())->toBe('Hosting');
});

it('entries() returns Section for chaining', function () {
    $section = Section::create('Test');
    $result = $section->entries([], fn ($item) => Entry::create('', ''));

    expect($result)->toBe($section);
});

it('entries() chains with other methods', function () {
    $items = [['name' => 'From Collection', 'url' => 'https://example.com']];

    $section = Section::create('Mixed')
        ->entry('Manual', 'https://example.com/manual')
        ->entries($items, fn ($item) => Entry::create($item['name'], $item['url']))
        ->entry('After', 'https://example.com/after');

    expect($section->getEntries())->toHaveCount(3);
});

it('entries() accepts any iterable', function () {
    $generator = (function () {
        yield ['name' => 'Item A', 'url' => 'https://a.com'];
        yield ['name' => 'Item B', 'url' => 'https://b.com'];
    })();

    $section = Section::create('Test')
        ->entries($generator, fn ($item) => Entry::create($item['name'], $item['url']));

    expect($section->getEntries())->toHaveCount(2);
});

<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\Section;

it('creates a section with a name', function () {
    $section = Section::create('Services');

    expect($section->getName())->toBe('Services')
        ->and($section->getLocale())->toBeNull()
        ->and($section->getEntries())->toHaveCount(0);
});

it('creates a section with a locale', function () {
    $section = Section::create('Leistungen', 'de');

    expect($section->getName())->toBe('Leistungen')
        ->and($section->getLocale())->toBe('de');
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

it('supports fluent locale setter', function () {
    $section = Section::create('Leistungen')->locale('de');

    expect($section->getLocale())->toBe('de');
});

it('renders name from a closure', function () {
    $section = Section::create(fn () => 'Closure Section');

    expect($section->render())->toBe('## Closure Section');
});

it('evaluates name closure at render time respecting current locale', function () {
    app()->setLocale('de');

    $section = Section::create(fn () => app()->getLocale() === 'de' ? 'Leistungen' : 'Services');

    expect($section->render())->toBe('## Leistungen');

    app()->setLocale('en');

    expect($section->render())->toBe('## Services');
});

it('getName evaluates closures', function () {
    $section = Section::create(fn () => 'Resolved Name');

    expect($section->getName())->toBe('Resolved Name');
});

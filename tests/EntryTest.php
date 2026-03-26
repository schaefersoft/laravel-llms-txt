<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\Entry;

it('creates an entry with all properties', function () {
    $entry = Entry::create('Web Development', 'https://schaefersoft.ch/services/web', 'Laravel apps', 'en');

    expect($entry->getTitle())->toBe('Web Development')
        ->and($entry->getUrl())->toBe('https://schaefersoft.ch/services/web')
        ->and($entry->getDescription())->toBe('Laravel apps')
        ->and($entry->getLocale())->toBe('en');
});

it('creates an entry with only title and url', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch');

    expect($entry->getTitle())->toBe('Home')
        ->and($entry->getUrl())->toBe('https://schaefersoft.ch')
        ->and($entry->getDescription())->toBeNull()
        ->and($entry->getLocale())->toBeNull();
});

it('renders an entry with description', function () {
    $entry = Entry::create('Web Development', 'https://schaefersoft.ch/services/web', 'Modern web apps');

    expect($entry->render())->toBe('- [Web Development](https://schaefersoft.ch/services/web): Modern web apps');
});

it('renders an entry without description', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch');

    expect($entry->render())->toBe('- [Home](https://schaefersoft.ch)');
});

it('renders as string via __toString', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch');

    expect((string) $entry)->toBe('- [Home](https://schaefersoft.ch)');
});

it('supports fluent setter chaining', function () {
    $entry = Entry::create('Placeholder', 'https://example.com')
        ->title('Real Title')
        ->url('https://schaefersoft.ch')
        ->description('A description')
        ->locale('de');

    expect($entry->getTitle())->toBe('Real Title')
        ->and($entry->getUrl())->toBe('https://schaefersoft.ch')
        ->and($entry->getDescription())->toBe('A description')
        ->and($entry->getLocale())->toBe('de');
});

it('renders an empty description as no description', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch', '');

    expect($entry->render())->toBe('- [Home](https://schaefersoft.ch)');
});

it('renders title from a closure', function () {
    $entry = Entry::create(fn () => 'Closure Title', 'https://schaefersoft.ch');

    expect($entry->render())->toBe('- [Closure Title](https://schaefersoft.ch)');
});

it('renders url from a closure', function () {
    $entry = Entry::create('Home', fn () => 'https://schaefersoft.ch');

    expect($entry->render())->toBe('- [Home](https://schaefersoft.ch)');
});

it('renders description from a closure', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch', fn () => 'Closure description');

    expect($entry->render())->toBe('- [Home](https://schaefersoft.ch): Closure description');
});

it('evaluates closures at render time respecting current locale', function () {
    app()->setLocale('de');

    $entry = Entry::create(
        fn () => app()->getLocale() === 'de' ? 'Webentwicklung' : 'Web Development',
        'https://schaefersoft.ch/services/web',
    );

    expect($entry->render())->toBe('- [Webentwicklung](https://schaefersoft.ch/services/web)');

    app()->setLocale('en');

    expect($entry->render())->toBe('- [Web Development](https://schaefersoft.ch/services/web)');
});

it('getters evaluate closures', function () {
    $entry = Entry::create(
        fn () => 'Closure Title',
        fn () => 'https://closure.url',
        fn () => 'Closure description',
    );

    expect($entry->getTitle())->toBe('Closure Title')
        ->and($entry->getUrl())->toBe('https://closure.url')
        ->and($entry->getDescription())->toBe('Closure description');
});

<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\Entry;

it('creates an entry with title, url and description', function () {
    $entry = Entry::create('Web Development', 'https://schaefersoft.ch/services/web', 'Laravel apps');

    expect($entry->getTitle())->toBe('Web Development')
        ->and($entry->getUrl())->toBe('https://schaefersoft.ch/services/web')
        ->and($entry->getDescription())->toBe('Laravel apps');
});

it('creates an entry with only title and url', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch');

    expect($entry->getTitle())->toBe('Home')
        ->and($entry->getUrl())->toBe('https://schaefersoft.ch')
        ->and($entry->getDescription())->toBeNull();
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
        ->description('A description');

    expect($entry->getTitle())->toBe('Real Title')
        ->and($entry->getUrl())->toBe('https://schaefersoft.ch')
        ->and($entry->getDescription())->toBe('A description');
});

it('renders an empty description as no description', function () {
    $entry = Entry::create('Home', 'https://schaefersoft.ch', '');

    expect($entry->render())->toBe('- [Home](https://schaefersoft.ch)');
});

it('accepts closures for title, url and description', function () {
    $entry = Entry::create(
        fn () => 'Closure Title',
        fn () => 'https://closure.example.com',
        fn () => 'Closure Description',
    );

    expect($entry->render())
        ->toBe('- [Closure Title](https://closure.example.com): Closure Description');
});

it('evaluates closure title at render time', function () {
    $locale = 'en';

    $entry = Entry::create(
        function () use (&$locale) {
            return $locale === 'de' ? 'Webentwicklung' : 'Web Development';
        },
        'https://schaefersoft.ch/services/web',
    );

    expect($entry->render())->toBe('- [Web Development](https://schaefersoft.ch/services/web)');

    $locale = 'de';

    expect($entry->render())->toBe('- [Webentwicklung](https://schaefersoft.ch/services/web)');
});

it('getters evaluate closures', function () {
    $entry = Entry::create(
        fn () => 'Dynamic Title',
        fn () => 'https://dynamic.example.com',
        fn () => 'Dynamic Description',
    );

    expect($entry->getTitle())->toBe('Dynamic Title')
        ->and($entry->getUrl())->toBe('https://dynamic.example.com')
        ->and($entry->getDescription())->toBe('Dynamic Description');
});

it('fluent setter accepts closures', function () {
    $entry = Entry::create('Placeholder', 'https://example.com')
        ->title(fn () => 'Closure Title')
        ->url(fn () => 'https://closure.example.com')
        ->description(fn () => 'Closure Desc');

    expect($entry->getTitle())->toBe('Closure Title')
        ->and($entry->getUrl())->toBe('https://closure.example.com')
        ->and($entry->getDescription())->toBe('Closure Desc');
});

<?php

declare(strict_types=1);

use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxtServiceProvider;
use SchaeferSoft\LaravelLlmsTxt\Section;

// ---------------------------------------------------------------------------
// LlmsTxt::make() alias
// ---------------------------------------------------------------------------

it('make() returns a LlmsTxt instance', function () {
    expect(LlmsTxt::make())->toBeInstanceOf(LlmsTxt::class);
});

it('make() behaves identically to create()', function () {
    $via_create = LlmsTxt::create()->title('My Site')->render();
    $via_make = LlmsTxt::make()->title('My Site')->render();

    expect($via_make)->toBe($via_create);
});

// ---------------------------------------------------------------------------
// LlmsTxt::section() closure shorthand
// ---------------------------------------------------------------------------

it('section() closure shorthand builds the correct section structure', function () {
    $output = LlmsTxt::make()
        ->title('My Site')
        ->section('Services', fn ($s) => $s
            ->entry('Web Dev', 'https://example.com/web', 'Laravel & Vue.js')
            ->entry('Hosting', 'https://example.com/hosting')
        )
        ->render();

    expect($output)
        ->toContain('## Services')
        ->toContain('- [Web Dev](https://example.com/web): Laravel & Vue.js')
        ->toContain('- [Hosting](https://example.com/hosting)');
});

it('section() pushes the section and returns LlmsTxt for chaining', function () {
    $llms = LlmsTxt::make()
        ->section('First', fn ($s) => $s->entry('A', 'https://a.com'))
        ->section('Second', fn ($s) => $s->entry('B', 'https://b.com'));

    expect($llms->getSections())->toHaveCount(2);
    expect($llms->getSections()->first()->getName())->toBe('First');
    expect($llms->getSections()->last()->getName())->toBe('Second');
});

it('section() accepts a Closure as title', function () {
    $llms = LlmsTxt::make()
        ->section(fn () => 'Dynamic Section', fn ($s) => $s->entry('Entry', 'https://example.com'));

    expect($llms->getSections()->first()->getName())->toBe('Dynamic Section');
});

it('addSection() still works unchanged alongside section()', function () {
    $llms = LlmsTxt::make()
        ->addSection(Section::create('Classic')->addEntry(Entry::create('E', 'https://e.com')))
        ->section('Fluent', fn ($s) => $s->entry('F', 'https://f.com'));

    expect($llms->getSections())->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// Section::entry() shorthand
// ---------------------------------------------------------------------------

it('entry() on Section adds entries correctly', function () {
    $section = Section::create('Docs')
        ->entry('Getting Started', 'https://example.com/start', 'Intro guide')
        ->entry('API Reference', 'https://example.com/api');

    expect($section->getEntries())->toHaveCount(2);

    $first = $section->getEntries()->first();
    expect($first->getTitle())->toBe('Getting Started');
    expect($first->getUrl())->toBe('https://example.com/start');
    expect($first->getDescription())->toBe('Intro guide');

    $second = $section->getEntries()->last();
    expect($second->getTitle())->toBe('API Reference');
    expect($second->getUrl())->toBe('https://example.com/api');
});

it('entry() returns the Section for chaining', function () {
    $section = Section::create('Test');
    $result = $section->entry('A', 'https://a.com');

    expect($result)->toBe($section);
});

it('entry() renders correctly', function () {
    $output = Section::create('Services')
        ->entry('Web Dev', 'https://example.com/web', 'Great description')
        ->entry('Hosting', 'https://example.com/hosting')
        ->render();

    expect($output)
        ->toContain('## Services')
        ->toContain('- [Web Dev](https://example.com/web): Great description')
        ->toContain('- [Hosting](https://example.com/hosting)');
});

it('entry() accepts Closure arguments', function () {
    $section = Section::create('Test')
        ->entry(fn () => 'Dynamic Title', fn () => 'https://example.com', fn () => 'Dynamic desc');

    $entry = $section->getEntries()->first();
    expect($entry->getTitle())->toBe('Dynamic Title');
    expect($entry->getUrl())->toBe('https://example.com');
    expect($entry->getDescription())->toBe('Dynamic desc');
});

it('addEntry() still works unchanged alongside entry()', function () {
    $section = Section::create('Test')
        ->addEntry(Entry::create('Classic', 'https://classic.com'))
        ->entry('Fluent', 'https://fluent.com');

    expect($section->getEntries())->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// LlmsTxt::when()
// ---------------------------------------------------------------------------

it('when() on LlmsTxt runs callback when condition is truthy', function () {
    $llms = LlmsTxt::make()
        ->title('Site')
        ->when(true, fn ($l) => $l->description('Added via when'));

    expect($llms->getDescription())->toBe('Added via when');
});

it('when() on LlmsTxt does not run callback when condition is falsy', function () {
    $llms = LlmsTxt::make()
        ->title('Site')
        ->when(false, fn ($l) => $l->description('Should not appear'));

    expect($llms->getDescription())->toBeNull();
});

it('when() on LlmsTxt accepts a Closure as condition', function () {
    $llms = LlmsTxt::make()
        ->when(fn () => true, fn ($l) => $l->title('Set via closure condition'));

    expect($llms->getTitle())->toBe('Set via closure condition');
});

it('when() on LlmsTxt returns LlmsTxt for chaining', function () {
    $llms = LlmsTxt::make();
    $result = $llms->when(true, fn ($l) => $l);

    expect($result)->toBe($llms);
});

it('when() on LlmsTxt integrates with section() chaining', function () {
    $output = LlmsTxt::make()
        ->title('Site')
        ->section('Core', fn ($s) => $s->entry('Home', 'https://example.com'))
        ->when(true, fn ($l) => $l->section('Extra', fn ($s) => $s->entry('API', 'https://example.com/api')))
        ->render();

    expect($output)
        ->toContain('## Core')
        ->toContain('## Extra');
});

// ---------------------------------------------------------------------------
// Section::when()
// ---------------------------------------------------------------------------

it('when() on Section runs callback when condition is truthy', function () {
    $section = Section::create('Services')
        ->entry('Web Dev', 'https://example.com/web')
        ->when(true, fn ($s) => $s->entry('Shop', 'https://example.com/shop'));

    expect($section->getEntries())->toHaveCount(2);
    expect($section->getEntries()->last()->getTitle())->toBe('Shop');
});

it('when() on Section does not run callback when condition is falsy', function () {
    $section = Section::create('Services')
        ->entry('Web Dev', 'https://example.com/web')
        ->when(false, fn ($s) => $s->entry('Should Not Appear', 'https://example.com/nope'));

    expect($section->getEntries())->toHaveCount(1);
});

it('when() on Section accepts a Closure as condition', function () {
    $section = Section::create('Services')
        ->when(fn () => true, fn ($s) => $s->entry('Added', 'https://example.com'));

    expect($section->getEntries())->toHaveCount(1);
});

it('when() on Section returns Section for chaining', function () {
    $section = Section::create('Test');
    $result = $section->when(true, fn ($s) => $s);

    expect($result)->toBe($section);
});

// ---------------------------------------------------------------------------
// Entry::withDescription()
// ---------------------------------------------------------------------------

it('withDescription() sets the description and returns Entry for chaining', function () {
    $entry = Entry::create('API Reference', 'https://example.com/api')
        ->withDescription('Complete reference for all endpoints.');

    expect($entry->getDescription())->toBe('Complete reference for all endpoints.');
});

it('withDescription() renders correctly', function () {
    $output = Entry::create('Docs', 'https://example.com/docs')
        ->withDescription('All the documentation.')
        ->render();

    expect($output)->toBe('- [Docs](https://example.com/docs): All the documentation.');
});

it('withDescription() accepts a Closure', function () {
    $entry = Entry::create('Test', 'https://example.com')
        ->withDescription(fn () => 'Dynamic description');

    expect($entry->getDescription())->toBe('Dynamic description');
});

it('withDescription() returns Entry for chaining', function () {
    $entry = Entry::create('Test', 'https://example.com');
    $result = $entry->withDescription('desc');

    expect($result)->toBe($entry);
});

// ---------------------------------------------------------------------------
// register_routes config & LlmsTxt::routes()
// ---------------------------------------------------------------------------

it('routes are not registered when register_routes is false', function () {
    // Swap router with a fresh one to test in isolation
    $freshRouter = new Router(new Dispatcher);
    $originalRouter = app('router');
    app()->instance('router', $freshRouter);

    config()->set('llms-txt.route_enabled', true);
    config()->set('llms-txt.register_routes', false);

    $provider = new LlmsTxtServiceProvider(app());
    $provider->boot();

    expect($freshRouter->has('llms-txt.index'))->toBeFalse();

    // Restore
    app()->instance('router', $originalRouter);
});

it('LlmsTxt::routes() registers routes correctly when called manually', function () {
    $freshRouter = new Router(new Dispatcher);
    $originalRouter = app('router');
    app()->instance('router', $freshRouter);

    LlmsTxt::routes();

    expect($freshRouter->has('llms-txt.index'))->toBeTrue();
    expect($freshRouter->has('llms-txt.full'))->toBeTrue();

    // Restore
    app()->instance('router', $originalRouter);
});

it('LlmsTxt::routes() is idempotent — no duplicate routes on double call', function () {
    $freshRouter = new Router(new Dispatcher);
    $originalRouter = app('router');
    app()->instance('router', $freshRouter);

    LlmsTxt::routes();
    LlmsTxt::routes(); // second call must be a no-op — no exception, no duplicates

    // Both named routes exist exactly once (no DuplicateRouteException thrown)
    expect($freshRouter->has('llms-txt.index'))->toBeTrue();
    expect($freshRouter->has('llms-txt.full'))->toBeTrue();

    // Restore
    app()->instance('router', $originalRouter);
});

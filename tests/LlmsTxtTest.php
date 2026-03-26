<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\Section;

// ---------------------------------------------------------------------------
// Basic rendering
// ---------------------------------------------------------------------------

it('renders a minimal document with only a title', function () {
    $output = LlmsTxt::create()->title('SchaeferSoft')->render();

    expect($output)->toBe("# SchaeferSoft\n");
});

it('renders a document with title and description', function () {
    $output = LlmsTxt::create()
        ->title('SchaeferSoft')
        ->description('Web development agency')
        ->render();

    expect($output)->toBe("# SchaeferSoft\n\n> Web development agency\n");
});

it('renders a full document with sections and entries', function () {
    $output = LlmsTxt::create()
        ->title('SchaeferSoft')
        ->description('Web development and software agency')
        ->addSection(
            Section::create('Services')
                ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web', 'Modern web apps'))
                ->addEntry(Entry::create('Hosting', 'https://schaefersoft.ch/services/hosting', 'Managed hosting'))
        )
        ->addSection(
            Section::create('References')
                ->addEntry(Entry::create('Our Projects', 'https://schaefersoft.ch/references', 'All client projects'))
        )
        ->render();

    $expected = implode("\n\n", [
        '# SchaeferSoft',
        '> Web development and software agency',
        "## Services\n- [Web Development](https://schaefersoft.ch/services/web): Modern web apps\n- [Hosting](https://schaefersoft.ch/services/hosting): Managed hosting",
        "## References\n- [Our Projects](https://schaefersoft.ch/references): All client projects",
    ])."\n";

    expect($output)->toBe($expected);
});

it('renders an empty document', function () {
    $output = LlmsTxt::create()->render();

    expect($output)->toBe("\n");
});

it('casts to string via __toString', function () {
    $llms = LlmsTxt::create()->title('Test');

    expect((string) $llms)->toBe("# Test\n");
});

// ---------------------------------------------------------------------------
// Closure-based values
// ---------------------------------------------------------------------------

it('renders title from a closure', function () {
    $output = LlmsTxt::create()->title(fn () => 'Closure Title')->render();

    expect($output)->toBe("# Closure Title\n");
});

it('renders description from a closure', function () {
    $output = LlmsTxt::create()
        ->title('SchaeferSoft')
        ->description(fn () => 'Closure description')
        ->render();

    expect($output)->toBe("# SchaeferSoft\n\n> Closure description\n");
});

it('renders correctly with mixed string and closure values', function () {
    $output = LlmsTxt::create()
        ->title(fn () => 'Closure Title')
        ->description('Plain description')
        ->addSection(
            Section::create(fn () => 'Closure Section')
                ->addEntry(Entry::create(
                    fn () => 'Closure Entry',
                    'https://schaefersoft.ch',
                    fn () => 'Closure desc',
                ))
        )
        ->render();

    expect($output)
        ->toContain('# Closure Title')
        ->toContain('> Plain description')
        ->toContain('## Closure Section')
        ->toContain('- [Closure Entry](https://schaefersoft.ch): Closure desc');
});

it('evaluates closures with the current locale at render time', function () {
    $llms = LlmsTxt::create()
        ->title(fn () => app()->getLocale() === 'de' ? 'SchaeferSoft DE' : 'SchaeferSoft EN')
        ->addSection(
            Section::create(fn () => app()->getLocale() === 'de' ? 'Leistungen' : 'Services')
                ->addEntry(Entry::create(
                    fn () => app()->getLocale() === 'de' ? 'Webentwicklung' : 'Web Development',
                    'https://schaefersoft.ch/services/web',
                ))
        );

    app()->setLocale('de');
    expect($llms->render())
        ->toContain('# SchaeferSoft DE')
        ->toContain('## Leistungen')
        ->toContain('- [Webentwicklung]');

    app()->setLocale('en');
    expect($llms->render())
        ->toContain('# SchaeferSoft EN')
        ->toContain('## Services')
        ->toContain('- [Web Development]');
});

// ---------------------------------------------------------------------------
// Fluent setters / getters
// ---------------------------------------------------------------------------

it('exposes fluent getters', function () {
    $llms = LlmsTxt::create()
        ->title('My Site')
        ->description('A description')
        ->locale('de');

    expect($llms->getTitle())->toBe('My Site')
        ->and($llms->getDescription())->toBe('A description')
        ->and($llms->getLocale())->toBe('de');
});

it('getters evaluate closures', function () {
    $llms = LlmsTxt::create()
        ->title(fn () => 'Closure Title')
        ->description(fn () => 'Closure Description');

    expect($llms->getTitle())->toBe('Closure Title')
        ->and($llms->getDescription())->toBe('Closure Description');
});

it('collects sections', function () {
    $llms = LlmsTxt::create()
        ->addSection(Section::create('One'))
        ->addSection(Section::create('Two'));

    expect($llms->getSections())->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// Locale-aware file path
// ---------------------------------------------------------------------------

it('writes to locale-prefixed path when locale is set', function () {
    Storage::fake('public');

    LlmsTxt::create()
        ->title('SchaeferSoft DE')
        ->locale('de')
        ->writeToDisk();

    Storage::disk('public')->assertExists('de/llms.txt');
});

it('writes to root path when no locale is set', function () {
    Storage::fake('public');

    LlmsTxt::create()
        ->title('SchaeferSoft')
        ->writeToDisk();

    Storage::disk('public')->assertExists('llms.txt');
});

it('writes llms-full.txt to locale-prefixed path', function () {
    Storage::fake('public');

    $mock = new MockHandler([
        new Response(200, [], 'Page content'),
    ]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    LlmsTxt::create()
        ->title('SchaeferSoft DE')
        ->locale('de')
        ->addSection(
            Section::create('Services')
                ->addEntry(Entry::create('Web', 'https://schaefersoft.ch/services'))
        )
        ->writeFullToDisk(null, $client);

    Storage::disk('public')->assertExists('de/llms-full.txt');
});

// ---------------------------------------------------------------------------
// Caching
// ---------------------------------------------------------------------------

it('returns rendered output without caching when cache is disabled', function () {
    config()->set('llms-txt.cache_enabled', false);

    $output = LlmsTxt::create()->title('SchaeferSoft')->getCached();

    expect($output)->toBe("# SchaeferSoft\n");
});

it('caches and returns output when cache is enabled', function () {
    config()->set('llms-txt.cache_enabled', true);
    config()->set('llms-txt.cache_ttl', 60);

    Cache::flush();

    $llms = LlmsTxt::create()->title('Cached Site');
    $output = $llms->getCached('test-llms-txt');

    expect($output)->toBe("# Cached Site\n")
        ->and(Cache::has('test-llms-txt'))->toBeTrue();
});

it('flushes the cache', function () {
    config()->set('llms-txt.cache_enabled', true);

    Cache::put('llms-txt-flush-test', 'value', 60);

    $llms = LlmsTxt::create();
    $llms->flushCache('llms-txt-flush-test');

    expect(Cache::has('llms-txt-flush-test'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// llms-full.txt rendering
// ---------------------------------------------------------------------------

it('renders full document and appends fetched content', function () {
    $mock = new MockHandler([
        new Response(200, [], 'Fetched page content for web dev'),
        new Response(200, [], 'Fetched page content for hosting'),
    ]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    $output = LlmsTxt::create()
        ->title('SchaeferSoft')
        ->addSection(
            Section::create('Services')
                ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web'))
                ->addEntry(Entry::create('Hosting', 'https://schaefersoft.ch/services/hosting'))
        )
        ->renderFull($client);

    expect($output)
        ->toContain('# SchaeferSoft')
        ->toContain('## Services')
        ->toContain('- [Web Development](https://schaefersoft.ch/services/web)')
        ->toContain('Fetched page content for web dev')
        ->toContain('Fetched page content for hosting');
});

it('silently skips entries when fetch fails', function () {
    $mock = new MockHandler([
        new Response(500, [], 'Server error'),
    ]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    $output = LlmsTxt::create()
        ->title('SchaeferSoft')
        ->addSection(
            Section::create('Services')
                ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web'))
        )
        ->renderFull($client);

    expect($output)
        ->toContain('- [Web Development](https://schaefersoft.ch/services/web)')
        ->not->toContain('Server error');
});

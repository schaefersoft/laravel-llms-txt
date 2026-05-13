<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxtServiceProvider;

it('registers the llms.txt route', function () {
    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

it('registers the llms-full.txt route', function () {
    $this->get('/llms-full.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

it('serves content from configured LlmsTxt instance', function () {
    config()->set('llms-txt.cache_enabled', false);

    LlmsTxt::configure(fn ($llms) => $llms
        ->title('Configured Site')
        ->description('Set via configure()')
        ->section('Docs', fn ($s) => $s
            ->entry('Getting Started', 'https://example.com/docs')
        )
    );

    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertSee('# Configured Site', false)
        ->assertSee('> Set via configure()', false)
        ->assertSee('## Docs', false);
});

it('registers localized routes when localize_routes is enabled', function () {
    // Re-boot provider with localized routes config.
    config()->set('llms-txt.localize_routes', true);
    config()->set('llms-txt.locales', ['de', 'en']);

    // Manually trigger route registration since config was set after boot.
    $provider = new LlmsTxtServiceProvider(app());
    $provider->boot();

    $this->get('/de/llms.txt')->assertStatus(200);
    $this->get('/en/llms.txt')->assertStatus(200);
    $this->get('/de/llms-full.txt')->assertStatus(200);
    $this->get('/en/llms-full.txt')->assertStatus(200);
});

it('returns 404 for unregistered locale segments', function () {
    config()->set('llms-txt.localize_routes', true);
    config()->set('llms-txt.locales', ['de', 'en']);

    $provider = new LlmsTxtServiceProvider(app());
    $provider->boot();

    $this->get('/fr/llms.txt')->assertStatus(404);
});

it('sets the application locale when a localized route is hit', function () {
    config()->set('llms-txt.localize_routes', true);
    config()->set('llms-txt.locales', ['de', 'en']);
    config()->set('llms-txt.cache_enabled', false);

    LlmsTxt::configure(fn ($llms) => $llms
        ->title(app()->getLocale() === 'de' ? 'Titel DE' : 'Title EN')
    );

    $provider = new LlmsTxtServiceProvider(app());
    $provider->boot();

    $this->get('/de/llms.txt')
        ->assertStatus(200)
        ->assertSee('# Titel DE', false);

    $this->get('/en/llms.txt')
        ->assertStatus(200)
        ->assertSee('# Title EN', false);
});

it('merges the default config', function () {
    expect(config('llms-txt.route_enabled'))->toBeTrue()
        ->and(config('llms-txt.llms_txt_route'))->toBe('/llms.txt')
        ->and(config('llms-txt.llms_full_txt_route'))->toBe('/llms-full.txt')
        ->and(config('llms-txt.cache_enabled'))->toBeFalse() // overridden in TestCase
        ->and(config('llms-txt.disk'))->toBe('public');
});

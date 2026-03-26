<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxtServiceProvider;
use SchaeferSoft\LaravelLlmsTxt\Section;

it('registers the llms.txt route', function () {
    config()->set('llms-txt.route_enabled', true);

    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

it('registers the llms-full.txt route', function () {
    config()->set('llms-txt.route_enabled', true);

    $this->get('/llms-full.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

it('serves content from bound LlmsTxt instance', function () {
    config()->set('llms-txt.route_enabled', true);
    config()->set('llms-txt.cache_enabled', false);

    app()->bind(LlmsTxt::class, function () {
        return LlmsTxt::create()
            ->title('Bound Site')
            ->description('Bound from container')
            ->addSection(
                Section::create('Docs')
                    ->addEntry(Entry::create('Getting Started', 'https://example.com/docs'))
            );
    });

    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertSee('# Bound Site', false)
        ->assertSee('> Bound from container', false)
        ->assertSee('## Docs', false);

    app()->forgetInstance(LlmsTxt::class);
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

it('merges the default config', function () {
    expect(config('llms-txt.route_enabled'))->toBeTrue()
        ->and(config('llms-txt.llms_txt_route'))->toBe('/llms.txt')
        ->and(config('llms-txt.llms_full_txt_route'))->toBe('/llms-full.txt')
        ->and(config('llms-txt.cache_enabled'))->toBeFalse() // overridden in TestCase
        ->and(config('llms-txt.disk'))->toBe('public');
});

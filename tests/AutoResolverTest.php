<?php

declare(strict_types=1);

use SchaeferSoft\LaravelLlmsTxt\AutoResolver;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

// ---------------------------------------------------------------------------
// AutoResolver::resolve()
// ---------------------------------------------------------------------------

it('resolves GET routes into a valid LlmsTxt instance', function () {
    app('router')->get('/auto-resolver-test-page', fn () => 'test')->name('auto.resolver.test');

    $llmsTxt = AutoResolver::resolve();

    expect($llmsTxt)->toBeInstanceOf(LlmsTxt::class);

    $sections = $llmsTxt->getSections();
    expect($sections)->toHaveCount(1);
    expect($sections->first()->getName())->toBe('Routes');

    $entryUrls = $sections->first()->getEntries()->map(fn ($e) => $e->getUrl());
    expect($entryUrls)->toContain(url('/auto-resolver-test-page'));
});

it('excludes the llms.txt routes themselves from auto-resolved entries', function () {
    $llmsTxt = AutoResolver::resolve();

    $section = $llmsTxt->getSections()->first();
    $entryUrls = $section->getEntries()->map(fn ($e) => $e->getUrl());

    expect($entryUrls)->not->toContain(url('/llms.txt'));
    expect($entryUrls)->not->toContain(url('/llms-full.txt'));
});

it('uses the route name as entry title when available', function () {
    app('router')->get('/named-auto-test', fn () => 'test')->name('auto.named.route');

    $llmsTxt = AutoResolver::resolve();

    $entryTitles = $llmsTxt->getSections()->first()->getEntries()->map(fn ($e) => $e->getTitle());
    expect($entryTitles)->toContain('auto.named.route');
});

it('uses the URI as entry title when no route name is present', function () {
    app('router')->get('/unnamed-auto-test-path', fn () => 'test');

    $llmsTxt = AutoResolver::resolve();

    $entryTitles = $llmsTxt->getSections()->first()->getEntries()->map(fn ($e) => $e->getTitle());
    expect($entryTitles)->toContain('unnamed-auto-test-path');
});

it('sets the app name as the document title', function () {
    config()->set('app.name', 'My Test App');

    $llmsTxt = AutoResolver::resolve();

    expect($llmsTxt->getTitle())->toBe('My Test App');
});

it('excludes routes starting with internal prefixes', function () {
    app('router')->get('/_internal-route', fn () => 'test');
    app('router')->get('/telescope/requests', fn () => 'test');

    $llmsTxt = AutoResolver::resolve();

    $entryUrls = $llmsTxt->getSections()->first()->getEntries()->map(fn ($e) => $e->getUrl());
    expect($entryUrls)->not->toContain(url('/_internal-route'));
    expect($entryUrls)->not->toContain(url('/telescope/requests'));
});

it('configure() takes precedence over auto-resolving in the controller', function () {
    config()->set('llms-txt.cache_enabled', false);

    LlmsTxt::configure(fn ($llms) => $llms->title('Explicit Configuration'));

    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertSee('# Explicit Configuration', false);
});

it('excludes routes with URI parameters from auto-resolved entries', function () {
    app('router')->get('/services/{service:slug}', fn () => 'test')->name('services.show');
    app('router')->get('/blog/{post}', fn () => 'test')->name('blog.show');

    $llmsTxt = AutoResolver::resolve();

    $entryUrls = $llmsTxt->getSections()->first()->getEntries()->map(fn ($e) => $e->getUrl());
    expect($entryUrls)->not->toContain(url('/services/{service:slug}'));
    expect($entryUrls)->not->toContain(url('/blog/{post}'));
});

it('falls back to auto-resolved content when no manual binding exists', function () {
    config()->set('llms-txt.cache_enabled', false);

    app('router')->get('/auto-fallback-detect', fn () => 'test');

    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertSee(url('/auto-fallback-detect'), false);
});

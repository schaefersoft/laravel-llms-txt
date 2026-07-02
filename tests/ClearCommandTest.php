<?php

declare(strict_types=1);

it('flushes all package cache keys via llms:clear', function () {
    config()->set('llms-txt.locales', ['de', 'fr']);

    cache()->put('llms-txt', 'stale', 3600);
    cache()->put('llms-txt-full', 'stale', 3600);
    cache()->put('llms-txt.de', 'stale', 3600);
    cache()->put('llms-txt-full.de', 'stale', 3600);
    cache()->put('llms-txt.fr', 'stale', 3600);
    cache()->put('llms-txt.'.app()->getLocale(), 'stale', 3600);

    $this->artisan('llms:clear')
        ->expectsOutputToContain('llms.txt cache cleared')
        ->assertExitCode(0);

    expect(cache()->get('llms-txt'))->toBeNull()
        ->and(cache()->get('llms-txt-full'))->toBeNull()
        ->and(cache()->get('llms-txt.de'))->toBeNull()
        ->and(cache()->get('llms-txt-full.de'))->toBeNull()
        ->and(cache()->get('llms-txt.fr'))->toBeNull()
        ->and(cache()->get('llms-txt.'.app()->getLocale()))->toBeNull();
});

it('leaves unrelated cache keys untouched', function () {
    cache()->put('unrelated-key', 'keep me', 3600);

    $this->artisan('llms:clear')->assertExitCode(0);

    expect(cache()->get('unrelated-key'))->toBe('keep me');
});

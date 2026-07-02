<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

beforeEach(function () {
    Storage::fake('public');
});

it('generates llms.txt via artisan command', function () {
    LlmsTxt::configure(fn ($llms) => $llms
        ->title('SchaeferSoft')
        ->section('Services', fn ($s) => $s
            ->entry('Web', 'https://schaefersoft.ch/services/web')
        )
    );

    $this->artisan('llms:generate')
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('llms.txt');
    expect(Storage::disk('public')->get('llms.txt'))->toContain('# SchaeferSoft');
});

it('generates llms.txt for a specific locale', function () {
    LlmsTxt::configure(fn ($llms) => $llms
        ->title('SchaeferSoft DE')
        ->section('Leistungen', fn ($s) => $s
            ->entry('Webentwicklung', 'https://schaefersoft.ch/de/leistungen')
        )
    );

    $this->artisan('llms:generate', ['--locale' => 'de'])
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('de/llms.txt');
});

it('generates llms.txt for all configured locales', function () {
    config()->set('llms-txt.locales', ['de', 'en']);

    LlmsTxt::configure(fn ($llms) => $llms->title('SchaeferSoft'));

    $this->artisan('llms:generate', ['--all-locales' => true])
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('de/llms.txt');
    Storage::disk('public')->assertExists('en/llms.txt');
});

it('outputs a warning and falls back when all-locales is set but no locales configured', function () {
    config()->set('llms-txt.locales', []);

    LlmsTxt::configure(fn ($llms) => $llms->title('SchaeferSoft'));

    $this->artisan('llms:generate', ['--all-locales' => true])
        ->expectsOutputToContain('No locales configured')
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('llms.txt');
});

it('writes directly into the public folder when no disk is configured', function () {
    config()->set('llms-txt.disk', null);

    LlmsTxt::configure(fn ($llms) => $llms->title('Public Folder'));

    $this->artisan('llms:generate')
        ->assertExitCode(0);

    $path = public_path('llms.txt');

    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('# Public Folder');

    unlink($path);
});

it('flushes cached output after successful generation', function () {
    config()->set('llms-txt.cache_enabled', true);

    cache()->put('llms-txt', 'stale output', 3600);
    cache()->put('llms-txt.'.app()->getLocale(), 'stale output', 3600);

    LlmsTxt::configure(fn ($llms) => $llms->title('Fresh'));

    $this->artisan('llms:generate')
        ->assertExitCode(0);

    expect(cache()->get('llms-txt'))->toBeNull()
        ->and(cache()->get('llms-txt.'.app()->getLocale()))->toBeNull();
});

it('generates both llms.txt and llms-full.txt with --full flag', function () {
    LlmsTxt::configure(fn ($llms) => $llms
        ->title('SchaeferSoft')
        ->section('Services', fn ($s) => $s
            ->entry('Web', 'https://httpbin.org/status/200')
        )
    );

    // We are not making real HTTP calls in tests; llms-full.txt will be
    // generated but fetched content will be empty (silently skipped).
    $this->artisan('llms:generate', ['--full' => true])
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('llms.txt');
    Storage::disk('public')->assertExists('llms-full.txt');
});

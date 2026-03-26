<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\Section;

beforeEach(function () {
    Storage::fake('public');
});

it('generates llms.txt via artisan command', function () {
    app()->bind(LlmsTxt::class, fn () => LlmsTxt::create()
        ->title('SchaeferSoft')
        ->addSection(
            Section::create('Services')
                ->addEntry(Entry::create('Web', 'https://schaefersoft.ch/services/web'))
        )
    );

    $this->artisan('llms:generate')
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('llms.txt');
    expect(Storage::disk('public')->get('llms.txt'))->toContain('# SchaeferSoft');
});

it('generates llms.txt for a specific locale', function () {
    app()->bind(LlmsTxt::class, fn () => LlmsTxt::create()
        ->title('SchaeferSoft DE')
        ->addSection(
            Section::create('Leistungen')
                ->addEntry(Entry::create('Webentwicklung', 'https://schaefersoft.ch/de/leistungen'))
        )
    );

    $this->artisan('llms:generate', ['--locale' => 'de'])
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('de/llms.txt');
});

it('generates llms.txt for all configured locales', function () {
    config()->set('llms-txt.locales', ['de', 'en']);

    app()->bind(LlmsTxt::class, fn () => LlmsTxt::create()
        ->title('SchaeferSoft')
    );

    $this->artisan('llms:generate', ['--all-locales' => true])
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('de/llms.txt');
    Storage::disk('public')->assertExists('en/llms.txt');
});

it('outputs a warning and falls back when all-locales is set but no locales configured', function () {
    config()->set('llms-txt.locales', []);

    app()->bind(LlmsTxt::class, fn () => LlmsTxt::create()->title('SchaeferSoft'));

    $this->artisan('llms:generate', ['--all-locales' => true])
        ->expectsOutputToContain('No locales configured')
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('llms.txt');
});

it('generates both llms.txt and llms-full.txt with --full flag', function () {
    app()->bind(LlmsTxt::class, fn () => LlmsTxt::create()
        ->title('SchaeferSoft')
        ->addSection(
            Section::create('Services')
                ->addEntry(Entry::create('Web', 'https://httpbin.org/status/200'))
        )
    );

    // We are not making real HTTP calls in tests; llms-full.txt will be
    // generated but fetched content will be empty (silently skipped).
    $this->artisan('llms:generate', ['--full' => true])
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('llms.txt');
    Storage::disk('public')->assertExists('llms-full.txt');
});

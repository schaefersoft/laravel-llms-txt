# laravel-llms-txt

[![Latest Version on Packagist](https://img.shields.io/packagist/v/schaefersoft/laravel-llms-txt.svg?style=flat-square)](https://packagist.org/packages/schaefersoft/laravel-llms-txt)
[![Tests](https://img.shields.io/github/actions/workflow/status/schaefersoft/laravel-llms-txt/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/schaefersoft/laravel-llms-txt/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/schaefersoft/laravel-llms-txt.svg?style=flat-square)](https://packagist.org/packages/schaefersoft/laravel-llms-txt)

Automatically generate [`llms.txt`](https://llmstxt.org) and `llms-full.txt` for Laravel applications, making your site more accessible to AI models and LLM-powered tools.

## What is llms.txt?

The [llms.txt standard](https://llmstxt.org) defines a simple Markdown file that helps AI models understand the structure and content of a website — similar to how `sitemap.xml` helps search engines. This package provides a fluent PHP API and an Artisan command to generate and serve these files from your Laravel application.

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13

## Installation

Install via Composer:

```bash
composer require schaefersoft/laravel-llms-txt
```

The package is auto-discovered by Laravel. Publish the config file:

```bash
php artisan vendor:publish --tag=llms-txt-config
```

## Configuration

After publishing, edit `config/llms-txt.php`:

```php
return [
    // Enable/disable the dynamic /llms.txt and /llms-full.txt routes
    'route_enabled' => true,

    'llms_txt_route'      => '/llms.txt',
    'llms_full_txt_route' => '/llms-full.txt',

    // Cache the generated output
    'cache_enabled' => true,
    'cache_ttl'     => 3600, // seconds

    // Filesystem disk for static file generation (php artisan llms:generate)
    'disk' => 'public',

    // Localization
    'locales'         => ['de', 'en'],   // supported locales
    'localize_routes' => false,          // register /de/llms.txt etc.
];
```

## Usage

### Binding your LlmsTxt definition

The recommended approach is to bind your `LlmsTxt` instance in a service provider so it is available for both dynamic routes and the Artisan command:

```php
// app/Providers/AppServiceProvider.php

use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\Section;

public function register(): void
{
    $this->app->bind(LlmsTxt::class, function () {
        return LlmsTxt::create()
            ->title('SchaeferSoft')
            ->description('Web development and software agency')
            ->addSection(
                Section::create('Services')
                    ->addEntry(Entry::create(
                        'Web Development',
                        'https://schaefersoft.ch/services/web',
                        'Modern web applications with Laravel and Vue.js',
                    ))
                    ->addEntry(Entry::create(
                        'Hosting',
                        'https://schaefersoft.ch/services/hosting',
                        'Managed hosting and DevOps',
                    ))
            )
            ->addSection(
                Section::create('References')
                    ->addEntry(Entry::create(
                        'Our Projects',
                        'https://schaefersoft.ch/references',
                        'Overview of all client projects',
                    ))
            );
    });
}
```

Once bound, visiting `/llms.txt` returns:

```
# SchaeferSoft

> Web development and software agency

## Services
- [Web Development](https://schaefersoft.ch/services/web): Modern web applications with Laravel and Vue.js
- [Hosting](https://schaefersoft.ch/services/hosting): Managed hosting and DevOps

## References
- [Our Projects](https://schaefersoft.ch/references): Overview of all client projects
```

### Extracting the binding into a dedicated service provider

As your `LlmsTxt` definition grows, you may want to move it out of `AppServiceProvider` into its own file. Create a dedicated provider:

```bash
php artisan make:provider LlmsTxtServiceProvider
```

```php
// app/Providers/LlmsTxtServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\Section;

class LlmsTxtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LlmsTxt::class, function () {
            return LlmsTxt::create()
                ->title('SchaeferSoft')
                ->description('Web development and software agency')
                ->addSection(
                    Section::create('Services')
                        ->addEntry(Entry::create(
                            'Web Development',
                            'https://schaefersoft.ch/services/web',
                            'Modern web applications with Laravel and Vue.js',
                        ))
                );
        });
    }
}
```

Then register it in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\LlmsTxtServiceProvider::class,
];
```

### Static file generation

Use the Artisan command to write static files to the `public/` directory:

```bash
# Generate public/llms.txt
php artisan llms:generate

# Generate both public/llms.txt and public/llms-full.txt
php artisan llms:generate --full

# Generate for a specific locale (writes to public/de/llms.txt)
php artisan llms:generate --locale=de

# Generate for all locales defined in config
php artisan llms:generate --all-locales

# Combine flags
php artisan llms:generate --all-locales --full
```

### Programmatic usage

You can also call the builder directly without binding it in the container:

```php
use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\Section;

$content = LlmsTxt::create()
    ->title('SchaeferSoft')
    ->description('Web development agency')
    ->addSection(
        Section::create('Services')
            ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web'))
    )
    ->render();

// Write to disk directly
LlmsTxt::create()
    ->title('SchaeferSoft')
    ->writeToDisk(); // writes to the configured disk as llms.txt

// Or specify a custom filename
LlmsTxt::create()
    ->title('SchaeferSoft')
    ->writeToDisk('custom/path/llms.txt');
```

## Localization

### Per-entry and per-section locale

Attach a locale to any `Entry` or `Section`:

```php
Section::create('Leistungen', 'de')
    ->addEntry(Entry::create(
        'Webentwicklung',
        'https://schaefersoft.ch/de/leistungen',
        'Moderne Webanwendungen mit Laravel und Vue.js',
        'de',
    ));
```

### Locale-prefixed routes

Enable locale-prefixed routes in `config/llms-txt.php`:

```php
'locales'         => ['de', 'en'],
'localize_routes' => true,
```

This registers `/de/llms.txt`, `/en/llms.txt`, `/de/llms-full.txt`, and `/en/llms-full.txt`.

### Locale-aware registration with LlmsTxtRegistry

For multilingual sites, use `LlmsTxtRegistry::forLocale()` to register a separate builder per locale. The package sets `app()->getLocale()` before resolving the instance, so the correct factory is picked automatically — including on the default route (`/llms.txt`) which uses your app's default locale.

```php
// app/Providers/LlmsTxtServiceProvider.php

use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxtRegistry;
use SchaeferSoft\LaravelLlmsTxt\Section;

public function register(): void
{
    LlmsTxtRegistry::forLocale('de', fn() =>
        LlmsTxt::create()
            ->title('SchaeferSoft')
            ->description('Webentwicklung und Softwareagentur')
            ->addSection(
                Section::create('Leistungen')
                    ->addEntry(Entry::create(
                        'Webentwicklung',
                        'https://schaefersoft.ch/leistungen/web',
                        'Moderne Webanwendungen mit Laravel und Vue.js',
                    ))
            )
    );

    LlmsTxtRegistry::forLocale('en', fn() =>
        LlmsTxt::create()
            ->title('SchaeferSoft')
            ->description('Web development and software agency')
            ->addSection(
                Section::create('Services')
                    ->addEntry(Entry::create(
                        'Web Development',
                        'https://schaefersoft.ch/en/services/web',
                        'Modern web applications with Laravel and Vue.js',
                    ))
            )
    );
}
```

- `/llms.txt` serves the factory for your app's default locale (e.g. `'de'`)
- `/en/llms.txt` serves the `'en'` factory

The registry takes priority over any `app()->bind(LlmsTxt::class, ...)` binding.

Writing to disk with a locale set will automatically use a locale-prefixed path:

```php
LlmsTxt::create()
    ->title('SchaeferSoft')
    ->locale('de')
    ->writeToDisk(); // writes to de/llms.txt on the configured disk
```

## llms-full.txt

The extended `llms-full.txt` format fetches the content of each entry URL and appends it below the entry. This gives AI models access to the full text of each linked page.

```php
$content = LlmsTxt::create()
    ->title('SchaeferSoft')
    ->addSection(
        Section::create('Services')
            ->addEntry(Entry::create('Web Development', 'https://schaefersoft.ch/services/web'))
    )
    ->renderFull(); // performs HTTP requests for each entry URL

// Or write directly to disk
LlmsTxt::create()
    ->title('SchaeferSoft')
    ->writeFullToDisk();
```

Entries whose URLs cannot be fetched are silently skipped.

## Caching

When `cache_enabled` is `true` in config, the rendered output is cached using Laravel's cache system:

```php
// Render and cache (uses the key 'llms-txt' by default)
$content = LlmsTxt::create()->title('SchaeferSoft')->getCached();

// Custom cache key
$content = LlmsTxt::create()->title('SchaeferSoft')->getCached('my-llms-cache-key');

// Flush the cache
LlmsTxt::create()->flushCache('my-llms-cache-key');
```

## Output format

### llms.txt

```
# Site Title

> Site description/tagline

## Section Name
- [Entry Title](https://url.com): Entry description

## Another Section
- [Entry Title](https://url.com): Entry description
```

### llms-full.txt

Same as `llms.txt`, but the fetched content of each URL is appended below its entry line.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for guidelines.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

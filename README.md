# Laravel LLMs.txt

Automatically generate `llms.txt` and `llms-full.txt` files for your Laravel application — helping AI models understand your website, just like `sitemap.xml` helps search engines.

Built according to the [llmstxt.org](https://llmstxt.org/) specification.

**Requirements:** PHP 8.2+ and Laravel 10, 11, 12, or 13.

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Building Your Document](#building-your-document)
  - [Title and Description](#title-and-description)
  - [Sections and Entries](#sections-and-entries)
  - [Fluent Shorthand](#fluent-shorthand)
  - [Conditional Content](#conditional-content)
- [Configuration](#configuration)
- [Routes](#routes)
  - [Automatic Route Registration](#automatic-route-registration)
  - [Manual Route Registration](#manual-route-registration)
- [Localization](#localization)
  - [Translating Content](#translating-content)
  - [Locale-Prefixed Routes](#locale-prefixed-routes)
  - [Using mcamara/laravel-localization](#using-mcamaralaravel-localization)
- [Static File Generation](#static-file-generation)
  - [Artisan Command](#artisan-command)
  - [Programmatic Export](#programmatic-export)
- [llms-full.txt](#llms-fulltxt)
- [Caching](#caching)
- [Output Format](#output-format)
- [Testing](#testing)
- [License](#license)

---

## Installation

```bash
composer require schaefersoft/laravel-llms-txt
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=llms-txt-config
```

That's it. The package is auto-discovered by Laravel, and `/llms.txt` is available immediately.

---

## Quick Start

The package works in two modes:

1. **Zero-config** — Without any setup, `/llms.txt` is automatically generated from all registered `GET` routes in your application. Internal routes (Telescope, Horizon, Debugbar) are excluded.

2. **Custom definition** (recommended) — Bind your own `LlmsTxt` instance in a service provider to have full control over the output. A manual binding always takes precedence over auto-generation.

```php
// app/Providers/AppServiceProvider.php

use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

public function register(): void
{
    $this->app->bind(LlmsTxt::class, function () {
        return LlmsTxt::make()
            ->title('My App')
            ->description('A short description of what this site offers.')
            ->section('Services', fn ($s) => $s
                ->entry('Web Development', 'https://example.com/web', 'Laravel & Vue.js')
                ->entry('Hosting', 'https://example.com/hosting', 'Managed hosting')
            );
    });
}
```

This produces:

```markdown
# My App

> A short description of what this site offers.

## Services
- [Web Development](https://example.com/web): Laravel & Vue.js
- [Hosting](https://example.com/hosting): Managed hosting
```

> **Tip:** For larger projects, consider a dedicated `LlmsTxtServiceProvider` to keep your `AppServiceProvider` clean.

---

## Building Your Document

### Title and Description

Every document starts with a title and an optional description:

```php
LlmsTxt::make()
    ->title('My App')
    ->description('Short tagline for the site.');
```

Both methods accept a string or a `Closure` for lazy evaluation (useful for [localization](#localization)):

```php
LlmsTxt::make()
    ->title(fn () => __('llms.title'))
    ->description(fn () => __('llms.description'));
```

### Sections and Entries

Sections group related entries under a heading. Entries are links with a title, URL, and optional description.

**Verbose style** — useful when you need to store references to entries:

```php
use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\Section;

$section = Section::create('Services')
    ->addEntry(Entry::create('Web Development', 'https://example.com/web', 'Laravel & Vue.js'))
    ->addEntry(Entry::create('Hosting', 'https://example.com/hosting'));

LlmsTxt::make()
    ->title('My App')
    ->addSection($section);
```

The `Entry::create()` method also supports a fluent `withDescription()` call:

```php
Entry::create('API Reference', 'https://example.com/api')
    ->withDescription('All endpoints, auth & rate limits');
```

### Fluent Shorthand

For a more compact style, use `section()` and `entry()` directly on the builder:

```php
LlmsTxt::make()
    ->title('My App')
    ->section('Services', fn ($s) => $s
        ->entry('Web Development', 'https://example.com/web', 'Laravel & Vue.js')
        ->entry('Hosting', 'https://example.com/hosting', 'Managed hosting')
    )
    ->section('References', fn ($s) => $s
        ->entry('Projects', 'https://example.com/references', 'All client projects')
    );
```

> `create()` and `make()` are identical — use whichever you prefer.

> **Note:** The `entry()` shorthand returns the `Section`, not the `Entry`. To use `withDescription()`, use `Entry::create()` directly or pass the description as the third argument to `entry()`.

### Conditional Content

Both `LlmsTxt` and `Section` support a `when()` method that mirrors Laravel's own `when()`:

```php
LlmsTxt::make()
    ->title('My App')
    ->section('Services', fn ($s) => $s
        ->entry('Web Development', 'https://example.com/web')
        ->when((bool) config('features.shop'), fn ($s) => $s
            ->entry('Shop', 'https://example.com/shop')
        )
    )
    ->when((bool) config('features.api'), fn ($llms) => $llms
        ->section('API', fn ($s) => $s
            ->entry('API Docs', 'https://example.com/api')
        )
    );
```

You can also pass a `Closure` as the condition for lazy evaluation:

```php
->when(fn () => Feature::active('shop'), fn ($s) => $s
    ->entry('Shop', 'https://example.com/shop')
)
```

---

## Configuration

After publishing, the config file is at `config/llms-txt.php`:

| Option | Default | Description |
|---|---|---|
| `route_enabled` | `true` | Enable or disable the HTTP routes entirely. |
| `llms_txt_route` | `'/llms.txt'` | URL path for the standard file. |
| `llms_full_txt_route` | `'/llms-full.txt'` | URL path for the full file. |
| `register_routes` | `true` | Auto-register routes. Set to `false` for [manual registration](#manual-route-registration). |
| `cache_enabled` | `true` | Cache rendered output. |
| `cache_ttl` | `3600` | Cache lifetime in seconds. |
| `disk` | `'public'` | Filesystem disk used by the Artisan command. |
| `locales` | `[]` | List of supported locales (e.g. `['en', 'de']`). |
| `localize_routes` | `false` | Register locale-prefixed routes like `/en/llms.txt`. |

---

## Routes

### Automatic Route Registration

By default, the package registers two routes:

| Route | Description |
|---|---|
| `GET /llms.txt` | Serves the standard `llms.txt` output. |
| `GET /llms-full.txt` | Serves the `llms-full.txt` output (with fetched content). |

These are registered automatically when `register_routes` is `true` (the default).

### Manual Route Registration

To apply custom middleware or headers, disable automatic registration and call `LlmsTxt::routes()` yourself:

```php
// config/llms-txt.php
'register_routes' => false,
```

```php
// routes/web.php
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;

Route::middleware(['web', 'cache.headers:public;max_age=3600'])
    ->group(function () {
        LlmsTxt::routes();
    });
```

`LlmsTxt::routes()` is idempotent — it is safe to call more than once.

---

## Localization

### Translating Content

A single container binding covers all locales. The package sets the application locale before resolving your binding, so `__()` and `route()` return the correct values automatically.

```php
$this->app->bind(LlmsTxt::class, function () {
    return LlmsTxt::create()
        ->title(__('llms.title'))
        ->description(__('llms.description'))
        ->addSection(
            Section::create(__('llms.sections.services'))
                ->addEntry(Entry::create(
                    __('llms.entries.web_dev'),
                    route('services.web'),
                    __('llms.entries.web_dev_desc'),
                ))
        );
});
```

Create your language files as usual:

```php
// lang/en/llms.php
return [
    'title'       => 'My App',
    'description' => 'Software agency',
    'sections'    => ['services' => 'Services'],
    'entries'     => [
        'web_dev'      => 'Web Development',
        'web_dev_desc' => 'Modern web applications with Laravel & Vue.js',
    ],
];
```

```php
// lang/de/llms.php
return [
    'title'       => 'Meine App',
    'description' => 'Software-Agentur',
    'sections'    => ['services' => 'Leistungen'],
    'entries'     => [
        'web_dev'      => 'Webentwicklung',
        'web_dev_desc' => 'Moderne Webanwendungen mit Laravel & Vue.js',
    ],
];
```

### Locale-Prefixed Routes

Enable locale-prefixed routes to serve translated versions at `/en/llms.txt`, `/de/llms.txt`, etc.:

```php
// config/llms-txt.php
'locales'          => ['en', 'de'],
'localize_routes'  => true,
```

Unknown locale segments return a 404 response.

### Using mcamara/laravel-localization

If you use `mcamara/laravel-localization`, disable automatic registration and wrap the routes in a localized group:

```php
// config/llms-txt.php
'register_routes' => false,
```

```php
// routes/web.php
Route::group(
    ['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['localize']],
    function () {
        LlmsTxt::routes();
    }
);
```

---

## Static File Generation

### Artisan Command

Generate static files with the `llms:generate` command:

```bash
# Generate public/llms.txt
php artisan llms:generate

# Also generate public/llms-full.txt
php artisan llms:generate --full

# Generate for a specific locale (e.g. public/de/llms.txt)
php artisan llms:generate --locale=de

# Generate for all configured locales
php artisan llms:generate --all-locales

# Combine flags
php artisan llms:generate --all-locales --full
```

Files are written to the filesystem disk configured via the `disk` option (default: `public`).

### Programmatic Export

You can also write files to disk from code:

```php
// Write to the default location
LlmsTxt::make()->title('My App')->writeToDisk();

// Write to a custom path
LlmsTxt::make()->title('My App')->writeToDisk('custom/path/llms.txt');

// Write with a locale prefix (writes to de/llms.txt)
LlmsTxt::make()->title('My App')->locale('de')->writeToDisk();

// Write the full version
LlmsTxt::make()->title('My App')->writeFullToDisk();
```

---

## llms-full.txt

The full variant fetches the content of each entry URL and appends it below the entry. URLs that cannot be fetched are silently skipped.

```php
// Render as a string
$content = LlmsTxt::make()
    ->title('My App')
    ->section('Docs', fn ($s) => $s
        ->entry('API', 'https://example.com/api')
    )
    ->renderFull();

// Write directly to disk
LlmsTxt::make()->title('My App')->writeFullToDisk();
```

The route `/llms-full.txt` serves this output dynamically.

---

## Caching

When `cache_enabled` is `true` (the default), rendered output is cached for the duration specified by `cache_ttl`.

```php
// Use the default cache key ('llms-txt')
$content = LlmsTxt::make()->title('My App')->getCached();

// Use a custom cache key
$content = LlmsTxt::make()->title('My App')->getCached('my-custom-key');

// Flush the cache
LlmsTxt::make()->flushCache();           // default key
LlmsTxt::make()->flushCache('my-custom-key');
```

---

## Output Format

### llms.txt

```markdown
# Site Title

> Site description

## Section Name
- [Entry Title](https://example.com): Entry description
- [Another Entry](https://example.com/other)
```

### llms-full.txt

Same structure as `llms.txt`, but with the fetched HTML/text content of each URL appended below its entry.

---

## Testing

```bash
composer test
```

## License

MIT — see [LICENSE](LICENSE).

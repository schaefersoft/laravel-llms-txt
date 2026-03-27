# laravel-llms-txt

Automatically generate `llms.txt` and `llms-full.txt` for Laravel applications.

> **What is `llms.txt`?**  
> A simple Markdown file that helps AI models understand the structure of a website — similar to how `sitemap.xml` helps search engines. Structure according to [llmstxt.org](https://llmstxt.org/).

**Requirements:** PHP 8.2+ · Laravel 10, 11, 12 or 13

---

## Installation

```bash
composer require schaefersoft/laravel-llms-txt
php artisan vendor:publish --tag=llms-txt-config
```

The package is auto-discovered by Laravel. After installation, `/llms.txt` is available immediately — no further configuration required.

---

## Configuration

`config/llms-txt.php`:

```php
return [
    'route_enabled'       => true,
    'llms_txt_route'      => '/llms.txt',
    'llms_full_txt_route' => '/llms-full.txt',

    // false = register routes manually (see below)
    'register_routes'     => true,

    'cache_enabled'       => true,
    'cache_ttl'           => 3600, // seconds

    // Disk for php artisan llms:generate
    'disk'                => 'public',

    // Localization
    'locales'             => ['de', 'en'],
    'localize_routes'     => false, // register /de/llms.txt etc.
];
```

---

## Usage

### Zero-config

If no custom definition is bound in the container, `/llms.txt` is automatically built from all registered GET routes in your application. A manual binding always takes precedence.

### Custom definition (recommended)

Register your definition in a service provider so it is available for both dynamic routes and the Artisan command.

```php
// app/Providers/AppServiceProvider.php

use SchaeferSoft\LaravelLlmsTxt\Entry;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\Section;

public function register(): void
{
    $this->app->bind(LlmsTxt::class, function () {
        return LlmsTxt::create()
            ->title('My App')
            ->description('Short description')
            ->addSection(
                Section::create('Services')
                    ->addEntry(Entry::create(
                        'Web Development',
                        'https://example.com/services/web',
                        'Laravel & Vue.js',
                    ))
            );
    });
}
```

For larger projects, consider a dedicated provider:

```bash
php artisan make:provider LlmsTxtServiceProvider
```

```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\LlmsTxtServiceProvider::class,
];
```

#### Output

```markdown
# My App

> Short description

## Services
- [Web Development](https://example.com/services/web): Laravel & Vue.js
```

---

## Fluent API

`create()` and `make()` are identical static factories. Use `section()` and `entry()` for a more compact style:

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

`withDescription()` is available when using `Entry::create()` directly:

```php
Entry::create('API Reference', 'https://example.com/api')
    ->withDescription('All endpoints, auth & rate limits');
```

> **Note:** `withDescription()` is not reachable via the `section()->entry()` shorthand, as that returns the `Section`, not the `Entry`. Pass the description as the third argument to `entry()` instead.

### Conditional content with `when()`

Both `LlmsTxt` and `Section` support `when()`, mirroring Laravel's own behavior:

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

Pass a Closure as the first argument for lazy evaluation:

```php
->when(fn () => Feature::active('shop'), fn ($s) => $s->entry('Shop', '...'))
```

---

## Localization

A single binding covers all locales. The package sets the locale automatically before resolving your binding, so `__()` and `route()` always return the correct value.

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

Language files:

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

### Locale-prefixed routes

```php
// config/llms-txt.php
'locales'         => ['de', 'en'],
'localize_routes' => true,
```

This registers `/de/llms.txt`, `/en/llms.txt` etc. automatically. Unknown locale segments return 404.

### With mcamara/laravel-localization

```php
// config/llms-txt.php
'register_routes' => false,

// routes/web.php
Route::group(
    ['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['localize']],
    function () {
        LlmsTxt::routes();
    }
);
```

---

## Manual route registration

To apply custom middleware or cache headers, disable automatic registration and call `LlmsTxt::routes()` yourself:

```php
// config/llms-txt.php
'register_routes' => false,

// routes/web.php
Route::middleware(['web', 'cache.headers:public;max_age=3600'])
    ->group(function () {
        LlmsTxt::routes(); // idempotent — safe to call more than once
    });
```

---

## Static file generation

```bash
# Generates public/llms.txt
php artisan llms:generate

# Generates public/llms.txt + public/llms-full.txt
php artisan llms:generate --full

# Generates public/de/llms.txt
php artisan llms:generate --locale=de

# Generates for all configured locales
php artisan llms:generate --all-locales
php artisan llms:generate --all-locales --full
```

Or write to disk programmatically:

```php
LlmsTxt::make()->title('My App')->writeToDisk();
LlmsTxt::make()->title('My App')->writeToDisk('custom/path/llms.txt');

// With locale prefix → writes to de/llms.txt
LlmsTxt::make()->title('My App')->locale('de')->writeToDisk();
```

---

## llms-full.txt

The `llms-full.txt` format fetches the content of each entry URL and appends it below the entry. URLs that cannot be fetched are silently skipped.

```php
// As a string
$content = LlmsTxt::create()
    ->title('My App')
    ->addSection(...)
    ->renderFull();

// Write directly to disk
LlmsTxt::create()->title('My App')->writeFullToDisk();
```

---

## Caching

```php
// Default cache key ('llms-txt')
$content = LlmsTxt::create()->title('...')->getCached();

// Custom cache key
$content = LlmsTxt::create()->title('...')->getCached('my-key');

// Flush the cache
LlmsTxt::create()->flushCache('my-key');
```

---

## Output format

### llms.txt

```markdown
# Site Title

> Description

## Section Name
- [Entry Title](https://url.com): Entry description
```

### llms-full.txt

Same as `llms.txt`, but the fetched content of each URL is appended below its entry.

---

## Testing

```bash
composer test
```

## License

MIT — see [LICENSE](LICENSE).

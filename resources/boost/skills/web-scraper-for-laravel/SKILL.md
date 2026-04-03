---
name: web-scraper-for-laravel
description: Build and work with the Web Scraper for Laravel package, including HTTP and API scraping, data extraction using CSS selectors, XPath, regex, JSON dot notation, Schema.org, and typed scrape schemas with match definitions.
---

# Web Scraper for Laravel

This package makes it easy to scrape external web pages in Laravel applications. It supports both standard HTTP requests and JavaScript-rendered pages, with multiple data extraction methods including CSS selectors, XPath, regular expressions, JSON dot notation, and Schema.org structured data.

## When to use this skill
Use this skill when you need to:
- Fetch and parse HTML content from external websites
- Extract structured data from web pages using CSS selectors, XPath, or regex
- Scrape JavaScript-rendered pages that require a browser
- Work with JSON APIs and extract nested data using dot notation
- Extract Schema.org structured data from `<script type="application/ld+json">` blocks
- Create typed scrape schemas using DTOs for consistent data extraction
- Implement caching to avoid repeated requests
- Register custom fetch drivers

## Drivers

### HTTP Scraping

Use `WebScraper::http()` for standard HTTP requests using Laravel's HTTP client:

```php
use Jez500\WebScraperForLaravel\Facades\WebScraper;

$scraper = WebScraper::http()
    ->from('https://example.com')
    ->get();

$body = $scraper->getBody();
```

### API Scraping

Use `WebScraper::api()` for JavaScript-rendered pages (requires an external scraper API service):

```php
$scraper = WebScraper::api()
    ->from('https://example.com')
    ->get();
```

### Custom Drivers

Register custom fetch drivers via `extend()`:

```php
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;

WebScraper::extend('my-driver', function () {
    return new class implements WebScraperDriverInterface {
        public function fetch(\Jez500\WebScraperForLaravel\AbstractWebScraper $scraper): string
        {
            // Custom fetch logic, return HTML string
        }
    };
});

$scraper = WebScraper::driver('my-driver')->from('https://example.com')->get();
```

You can also pass a class name string to `extend()` or directly to `driver()`.

## Request Configuration

Configure requests using chainable setter methods:

```php
$scraper = WebScraper::http()
    ->from('https://example.com')
    ->setUseCache(true)            // Enable/disable caching (default: true)
    ->setCacheMinsTtl(60)          // Cache TTL in minutes (default: 720)
    ->setConnectTimeout(10)        // Connection timeout in seconds (default: 30)
    ->setRequestTimeout(10)        // Request timeout in seconds (default: 30)
    ->setCookies('session=abc123') // Set cookie header
    ->setOptions(['key' => 'val']) // Driver-specific options
    ->get();
```

User agents are automatically rotated on each request via `UserAgentGenerator`.

## Data Extraction Methods

All extraction methods return `Illuminate\Support\Collection`, so you can use `->first()`, `->all()`, `->map()`, etc.

### CSS Selectors

```php
// Get text content of the first title element
$title = $scraper->getSelector('title')->first();

// Get an HTML attribute
$image = $scraper->getSelector('meta[property="og\:image"]', 'attr', ['content'])->first();

// Get all paragraph text as an array
$paragraphs = $scraper->getSelector('p')->all();

// Get inner HTML
$content = $scraper->getSelector('.article-body', 'html')->first();

// Use a custom closure for complex extraction
$data = $scraper->getSelector('div.card', function (Crawler $node) {
    return ['title' => $node->filter('h2')->text(), 'link' => $node->filter('a')->attr('href')];
})->all();
```

Note: colons in selectors (e.g. `og:image`) are automatically escaped for Symfony DomCrawler.

### XPath Expressions

```php
$h1 = $scraper->getXpath('//h1')->first();
$linkHref = $scraper->getXpath('//a', 'attr', ['href'])->first();
$images = $scraper->getXpath('//img[@class="product-image"]')->all();
```

### Regular Expressions

```php
$author = $scraper->getRegex('~"user"\:"(.*)"~')->first();
$emails = $scraper->getRegex('~[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}~')->all();
```

Returns captured group 1 (`$matches[1]`).

### JSON Data

```php
$name = WebScraper::http()
    ->from('https://api.example.com/data.json')
    ->get()
    ->getJson('user.name')
    ->first();

$tags = WebScraper::http()
    ->from('https://api.example.com/data.json')
    ->get()
    ->getJson('posts.*.tags')
    ->all();
```

Uses Laravel's `data_get()` helper for dot notation.

### Schema.org Structured Data

```php
// Extract all JSON-LD blocks from the page
$schemas = $scraper->getSchemaOrg()->all();

// Get the first schema
$schema = $scraper->getSchemaOrg()->first();
// e.g. ['@type' => 'Article', 'headline' => '...', ...]
```

### Direct DOM Access

```php
// Get the Symfony DomCrawler instance for advanced manipulation
$dom = $scraper->getDom();
```

## Typed Scrape Schemas

Use DTOs for structured, validated data extraction. The `fromDto()` method accepts a `ScrapeSchemaDto`, `FieldExtractionDto`, array, or JSON string.

### Basic Schema

```php
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;

$schema = ScrapeSchemaDto::fromArray([
    'fields' => [
        'title' => [
            'type' => 'css',
            'value' => 'title',
        ],
        'description' => [
            'type' => 'css',
            'value' => 'meta[name=description]|content',  // pipe syntax: extracts attribute
        ],
        'body' => [
            'type' => 'css',
            'value' => '!.article-body',  // ! prefix: extracts innerHTML
        ],
        'author' => [
            'type' => 'xpath',
            'value' => '//meta[@name="author"]/@content',
        ],
        'structured_data' => [
            'type' => 'schema_org',
        ],
    ],
]);

$data = WebScraper::http()
    ->from('https://example.com')
    ->get()
    ->fromDto($schema);

$data->get('title');       // Single field
$data->get('description'); // Extracted attribute value
$data->all();              // All fields as associative array
```

You can also pass an array or JSON string directly to `fromDto()`:

```php
$data = $scraper->fromDto([
    'fields' => [
        'title' => ['type' => 'css', 'value' => 'h1'],
    ],
]);
```

### Schema Field Types

| Type | Requires `value` | Description |
|------|:-:|---|
| `css` | Yes | CSS selector extraction |
| `xpath` | Yes | XPath expression extraction |
| `regex` | Yes | Regular expression extraction |
| `json` | Yes | JSON dot notation extraction |
| `schema_org` | No | Schema.org JSON-LD extraction |

### CSS Selector Shorthand in Schemas

When using `type: 'css'` in a schema field, the `value` supports shorthand syntax:

- **`selector`** — extracts text content (default)
- **`selector|attribute`** — extracts the named HTML attribute (e.g. `meta[name=description]|content`)
- **`!selector`** — extracts inner HTML (e.g. `!.rich-content`)

### Prepend / Append Transforms

Add static text before or after extracted values:

```php
$schema = ScrapeSchemaDto::fromArray([
    'fields' => [
        'image' => [
            'type' => 'css',
            'value' => 'img.hero|src',
            'prepend' => 'https://example.com',  // Prefix relative URLs
        ],
        'price' => [
            'type' => 'css',
            'value' => '.price',
            'append' => ' USD',
        ],
    ],
]);
```

### Match Definitions (Conditional Extraction)

Use `match` to conditionally resolve a field based on the extracted value:

```php
$schema = ScrapeSchemaDto::fromArray([
    'fields' => [
        'content_type' => [
            'type' => 'css',
            'value' => 'meta[name=type]|content',
            'match' => [
                'cases' => [
                    'article' => [
                        'type' => 'css',
                        'value' => '.article-body',
                    ],
                    'video' => [
                        'type' => 'css',
                        'value' => 'video|src',
                    ],
                ],
                'default' => [
                    'type' => 'css',
                    'value' => '.fallback-content',
                ],
            ],
        ],
    ],
]);
```

The extracted value is matched (case-insensitive, trimmed) against the `cases` keys. If no case matches, `default` is used. If no default is set, the original extracted value is returned.

## Testing

Use `WebScraper::fake()` for testing (no HTTP requests are made):

```php
use Jez500\WebScraperForLaravel\Facades\WebScraper;

$scraper = WebScraper::fake()
    ->setBody('<html><title>Test</title></html>');

$title = $scraper->getSelector('title')->first();
// 'Test'
```

You can also use Laravel's HTTP client faking for the HTTP driver:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'example.com/*' => Http::response('<html><title>Test</title></html>', 200),
]);

$scraper = WebScraper::http()->from('https://example.com')->get();
$title = $scraper->getSelector('title')->first();
```

## Error Handling

Handle scraping errors with try/catch. The package throws specific exceptions:

- `DomSelectorException` — invalid CSS selector or XPath expression
- `SchemaValidationException` — invalid schema definition (call `->errors()` for details)

```php
use Jez500\WebScraperForLaravel\Exceptions\DomSelectorException;
use Jez500\WebScraperForLaravel\Exceptions\SchemaValidationException;

try {
    $scraper = WebScraper::http()->from('https://example.com')->get();
    $title = $scraper->getSelector('title')->first();
} catch (DomSelectorException $e) {
    // Invalid selector
} catch (SchemaValidationException $e) {
    // Invalid schema — $e->errors() returns array of validation messages
} catch (\Exception $e) {
    // Network or other errors
}
```

Errors from fetch drivers are accumulated and accessible via `$scraper->getErrors()`.

## Key Classes

| Class | Namespace | Purpose |
|---|---|---|
| `WebScraper` (Facade) | `Jez500\WebScraperForLaravel\Facades` | Entry point |
| `WebScraperFactory` | `Jez500\WebScraperForLaravel` | Creates scraper instances |
| `AbstractWebScraper` | `Jez500\WebScraperForLaravel` | Base scraper with extraction methods |
| `ScrapeSchemaDto` | `Jez500\WebScraperForLaravel\Dto` | Schema definition DTO |
| `FieldExtractionDto` | `Jez500\WebScraperForLaravel\Dto` | Single field definition DTO |
| `MatchDefinitionDto` | `Jez500\WebScraperForLaravel\Dto` | Conditional match definition DTO |
| `SchemaCompiler` | `Jez500\WebScraperForLaravel\Schema` | Compiles and executes schemas |
| `SchemaValidator` | `Jez500\WebScraperForLaravel\Schema` | Validates schema structure |
| `WebScraperDriverInterface` | `Jez500\WebScraperForLaravel\Drivers` | Interface for custom drivers |

---
name: web-scraper-for-laravel
description: Build and work with the Web Scraper for Laravel package, including HTTP and API scraping, data extraction using CSS selectors, XPath, regex, and JSON dot notation, and typed scrape schemas.
---

# Web Scraper for Laravel

This package makes it easy to scrape external web pages in Laravel applications. It supports both standard HTTP requests and JavaScript-rendered pages, with multiple data extraction methods including CSS selectors, XPath, regular expressions, and JSON dot notation.

## When to use this skill
Use this skill when you need to:
- Fetch and parse HTML content from external websites
- Extract structured data from web pages using CSS selectors, XPath, or regex
- Scrape JavaScript-rendered pages that require a browser
- Work with JSON APIs and extract nested data using dot notation
- Create typed scrape schemas using DTOs for consistent data extraction
- Implement caching to avoid repeated requests
- Rotate user agents to avoid being blocked

## HTTP Scraping

Use `WebScraper::http()` for standard HTTP requests using Laravel's HTTP client:

```php
use Jez500\WebScraperForLaravel\Facades\WebScraper;

// Basic HTTP scraping
$scraper = WebScraper::http()
    ->from('https://example.com')
    ->get();

// Get the full page body
$body = $scraper->getBody();
```

## API Scraping

Use `WebScraper::api()` for JavaScript-rendered pages (requires `jez500/seleniumbase-scrapper`):

```php
// Scrape JavaScript-rendered page
$scraper = WebScraper::api()
    ->from('https://example.com')
    ->get();

$body = $scraper->getBody();
```

## Data Extraction Methods

### CSS Selectors

Extract elements using CSS selectors:

```php
// Get the first title element
$title = $scraper->getSelector('title')->first();

// Get the content attribute of a meta tag
$image = $scraper->getSelector('meta[property=og:image]|content')->first();

// Get all paragraph innerHtml as an array
$paragraphs = $scraper->getSelector('p')->all();

// Get a specific attribute
$links = $scraper->getSelector('a|href')->all();
```

### XPath Expressions

Extract elements using XPath:

```php
// Get the first h1 element
$h1 = $scraper->getXpath('//h1')->first();

// Get the href attribute of the first link
$linkHref = $scraper->getXpath('//a', 'attr', ['href'])->first();

// Get multiple elements
$images = $scraper->getXpath('//img[@class="product-image"]')->all();
```

### Regular Expressions

Extract data using regex patterns:

```php
// Extract user from JSON-like string
$author = $scraper->getRegex('~"user"\:"(.*)"~')->first();

// Extract email addresses
$emails = $scraper->getRegex('~[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}~')->all();
```

### JSON Data

Extract nested JSON data using dot notation:

```php
// Get JSON data from API response
$data = WebScraper::http()
    ->from('https://api.example.com/data.json')
    ->get()
    ->getJson('user.name')
    ->first();

// Extract nested array
$tags = WebScraper::http()
    ->from('https://api.example.com/data.json')
    ->get()
    ->getJson('posts.*.tags')
    ->all();
```

## Typed Scrape Schemas

Use DTOs for type-safe, validated data extraction:

```php
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;

// Define a schema
$schema = ScrapeSchemaDto::fromArray([
    'fields' => [
        'title' => [
            'type' => 'css',
            'value' => 'title',
        ],
        'description' => [
            'type' => 'css',
            'value' => 'meta[name=description]|content',
        ],
        'image' => [
            'type' => 'xpath',
            'value' => '//meta[@property="og:image"]/@content',
        ],
    ],
]);

// Apply schema and get structured data
$data = WebScraper::http()
    ->from('https://example.com')
    ->get()
    ->fromDto($schema);
```

Schema field types:
- `css`: Extract using CSS selector
- `xpath`: Extract using XPath expression
- `regex`: Extract using regular expression
- `json`: Extract from JSON using dot notation

## User Agent Rotation

Rotate user agents to avoid being blocked:

```php
$scraper = WebScraper::http()
    ->from('https://example.com')
    ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
    ->get();
```

## Caching

Cache responses to avoid repeated requests:

```php
// Cache for 1 hour
$scraper = WebScraper::http()
    ->from('https://example.com')
    ->cacheFor(3600)
    ->get();
```

## Error Handling

Always handle potential scraping errors:

```php
try {
    $scraper = WebScraper::http()
        ->from('https://example.com')
        ->get();

    $title = $scraper->getSelector('title')->first();
} catch (\Exception $e) {
    // Handle scraping errors
    logger()->error('Scraping failed', ['error' => $e->getMessage()]);
}
```

## Testing

When testing scraping logic, use Laravel's HTTP client mocking:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'example.com/*' => Http::response('<html><title>Test</title></html>', 200),
]);

$scraper = WebScraper::http()->from('https://example.com')->get();
$title = $scraper->getSelector('title')->first();

$this->assertEquals('Test', $title);
```

## Best Practices

- Always cache requests when data doesn't change frequently
- Use appropriate delays between requests to respect rate limits
- Check robots.txt before scraping
- Handle errors gracefully and implement retry logic for transient failures
- Use typed schemas for complex data extraction to ensure consistency
- Test scraping logic with mocked responses
- Respect website terms of service
- Consider using API endpoints when available instead of scraping HTML

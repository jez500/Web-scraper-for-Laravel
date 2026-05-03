# Web Scraper for Laravel

A powerful and flexible Laravel package for scraping external web pages and extracting structured data. Whether you need to fetch static HTML content or scrape JavaScript-rendered pages, this package provides a clean, fluent interface for all your web scraping needs.

## Key Features

* **HTTP Scraping**: Support for standard HTTP requests using Laravel's HTTP client
* **API Scraping**: Support for scraping JavaScript-rendered pages (using [jez500/seleniumbase-scrapper](https://github.com/jez500/seleniumbase-scrapper))
* **Extract Scraping**: Support for article extraction using [jez500/extract](https://github.com/jez500/extract-docker) (Mercury Parser)
* **Multiple Extraction Methods**: Extract data using CSS selectors, XPath expressions, regular expressions, or JSON dot notation
* **Typed Schemas**: Support for type-safe scrape schemas via DTOs and `fromDto(...)`
* **User Agent Rotation**: Built-in support for rotating user agents to avoid being blocked
* **Response Caching**: Cache responses to avoid repeated requests
* **Comprehensive Error Handling**: Robust error handling for network issues and parsing errors

## Installation

```shell
composer require jez500/web-scraper-for-laravel
```

For JavaScript-rendered page scraping, you'll also need to install:

```shell
composer require jez500/seleniumbase-scrapper
```

For article extraction, you'll need to run the Extract service:

```shell
docker run -p 3000:3000 jez500/extract
# or use your own instance and set the URL via setExtractApiBaseUrl()
```

## Quick Start

### Basic Scraping

```php
use Jez500\WebScraperForLaravel\Facades\WebScraper;

$scraper = WebScraper::http()->from('https://example.com')->get();
$title = $scraper->getSelector('title')->first();
```

### Extract Structured Data

```php
$data = WebScraper::http()
    ->from('https://example.com')
    ->get()
    ->getJson('user.email')
    ->first();
```

## Usage Examples

### Basic HTTP Scraping

```php
use Jez500\WebScraperForLaravel\Facades\WebScraper;

// Get an instance of the scraper with the body of the page loaded
$scraper = WebScraper::http()->from('https://example.com')->get();

// Get the full page body
$body = $scraper->getBody();
```

### CSS Selector Extraction

```php
// Get the first title element
$title = $scraper->getSelector('title')->first(); 

// Get the content attribute of the first meta tag with property og:image
$image = $scraper->getSelector('meta[property=og:image]|content')->first(); 

// Get all paragraph innerHtml as an array
$paragraphs = $scraper->getSelector('p')->all();

// Get all links as an array
$links = $scraper->getSelector('a|href')->all();

// Get the first matching element with a specific class
$price = $scraper->getSelector('.product-price')->first();
```

### XPath Extraction

```php
// Get the first h1 element
$h1 = $scraper->getXpath('//h1')->first();

// Get the href attribute of the first link
$linkHref = $scraper->getXpath('//a', 'attr', ['href'])->first();

// Get multiple elements with a specific attribute
$images = $scraper->getXpath('//img[@class="product-image"]')->all();

// Complex XPath query
$productTitle = $scraper->getXpath('//div[@class="product"]/h1')->first();
```

### Regular Expression Extraction

```php
// Extract user from JSON-like string
$author = $scraper->getRegex('~"user"\:"(.*)"~')->first();

// Extract email addresses
$emails = $scraper->getRegex('~[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}~')->all();

// Extract phone numbers
$phones = $scraper->getRegex('~\b\d{3}[-.]?\d{3}[-.]?\d{4}\b~')->all();
```

### JSON Data Extraction

```php
// Get nested JSON data from API response
$author = WebScraper::http()
    ->from('https://api.example.com/page.json')
    ->get()
    ->getJson('user.name')
    ->first();

// Extract all items from an array
$products = WebScraper::http()
    ->from('https://api.example.com/products.json')
    ->get()
    ->getJson('products.*.name')
    ->all();

// Extract multiple nested fields
$emails = WebScraper::http()
    ->from('https://api.example.com/users.json')
    ->get()
    ->getJson('users.*.email')
    ->all();
```

### JavaScript-Rendered Page Scraping

```php
// Get title from a JavaScript-rendered page
$title = WebScraper::api()
    ->from('https://example.com')
    ->get()
    ->getSelector('title')
    ->first();

// Extract data from a Single Page Application
$data = WebScraper::api()
    ->from('https://example.com/app')
    ->get()
    ->getSelector('[data-content]')
    ->first();
```

### Article Extraction

Extract clean article content from web pages using Mercury Parser:

```php
// Extract article content
$content = WebScraper::extract()
    ->from('https://example.com/article')
    ->get()
    ->getBody();

// With custom headers
$content = WebScraper::extract()
    ->from('https://example.com/article')
    ->setOptions(['headers' => ['Accept-Language' => 'en-US']])
    ->get()
    ->getBody();

// Use custom Extract API endpoint
$content = WebScraper::extract()
    ->setExtractApiBaseUrl('http://my-extract-service:3000')
    ->from('https://example.com/article')
    ->get()
    ->getBody();
```

### Custom Drivers

Register a custom driver in a service provider, then resolve it by name:

```php
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\AbstractWebScraper;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;
use Jez500\WebScraperForLaravel\Facades\WebScraper;

WebScraper::extend('residential-proxy', function () {
    return new class implements WebScraperDriverInterface {
        public function fetch(AbstractWebScraper $scraper): string
        {
            $response = Http::withToken(config('services.proxy.token'))
                ->post(config('services.proxy.endpoint'), [
                    'url' => $scraper->getUrl(),
                    'country' => 'us',
                ]);

            return data_get($response->json(), 'html', '');
        }
    };
});

$scraper = WebScraper::driver('residential-proxy')
    ->from('https://example.com')
    ->get();

$title = $scraper->getSelector('title')->first();
```

`http()` and `api()` remain available for backwards compatibility, and they now
resolve through the same driver layer internally.

### Typed Scrape Schemas

```php
use Jez500\WebScraperForLaravel\Dto\FieldExtractionDto;
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;

// Define a schema for structured data extraction
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

// $data will be: ['title' => '...', 'description' => '...', 'image' => '...']
```

`fromDto(...)` also accepts a single `FieldExtractionDto` when you only need one
extraction rule:

```php
$title = FieldExtractionDto::fromArray([
    'type' => 'css',
    'value' => 'title',
]);

$data = WebScraper::http()
    ->from('https://example.com')
    ->get()
    ->fromDto($title);

// $data will be: ['field' => 'Example Domain']
```

### Advanced Features

#### Custom User Agents

```php
// Set a custom user agent
$scraper = WebScraper::http()
    ->from('https://example.com')
    ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
    ->get();
```

#### Response Caching

```php
// Cache response for 1 hour
$scraper = WebScraper::http()
    ->from('https://example.com')
    ->cacheFor(3600)  // seconds
    ->get();
```

#### Error Handling

```php
try {
    $scraper = WebScraper::http()
        ->from('https://example.com')
        ->get();

    $title = $scraper->getSelector('title')->first();
} catch (\Exception $e) {
    // Handle scraping errors
    Log::error('Scraping failed', ['error' => $e->getMessage()]);
}
```

## Testing

When testing scraping logic, use Laravel's HTTP client mocking to avoid making actual network requests:

```php
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Facades\WebScraper;

public function test_scraping_basic_page()
{
    // Mock the HTTP response
    Http::fake([
        'example.com/*' => Http::response('<html><title>Test Page</title></html>', 200),
    ]);

    $scraper = WebScraper::http()->from('https://example.com')->get();
    $title = $scraper->getSelector('title')->first();

    $this->assertEquals('Test Page', $title);
}
```

## Best Practices

- **Always cache requests** when data doesn't change frequently to reduce server load
- **Use appropriate delays** between requests to respect rate limits and avoid being blocked
- **Check robots.txt** before scraping to ensure you're allowed to access the content
- **Handle errors gracefully** and implement retry logic for transient failures
- **Use typed schemas** for complex data extraction to ensure consistency and type safety
- **Test scraping logic** with mocked responses to ensure reliability
- **Respect website terms of service** and implement proper rate limiting
- **Consider using API endpoints** when available instead of scraping HTML
- **Validate extracted data** to ensure it meets your expected format
- **Log scraping activities** for debugging and monitoring purposes

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run coding standards and tests locally:
   ```shell
   composer analyse
   composer test
   ```
5. Commit your changes with clear, descriptive messages
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

When submitting PRs, please ensure:
- Code follows PSR standards
- Tests are included for new features
- Documentation is updated as needed
- All existing tests pass

## Support

For bug reports, feature requests, or questions, please open an issue on GitHub.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Author

[Jeremy Graham](https://github.com/jez500)

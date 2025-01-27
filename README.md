# Web Scraper for Laravel

A package to make it easier to scrape external web pages using laravel.

## Key Features
* Support for standard HTTP requests (Using Laravel's HTTP client)
* Support for scraping javascript rendered pages (Using https://github.com/amerkurev/scrapper)
* Rotating user agents to avoid being blocked
* Support for extracting data using CSS selectors
* Support for extracting data using dot notation from JSON responses
* Support for extracting data using regular expressions
* Caching responses to avoid repeated requests

## Usage examples

```php
use Jez500\WebScraperForLaravel\Facades\WebScraper;

// Get an instance of the scraper with the body of the page loaded.
$scraper = WebScraper::http()->from('https://example.com')->get();

// Get the full page body
$body = $scraper->getBody();

// Get the first title element
$title = $scraper->getSelector('title')->first(); 

// Get the content attribute of the first meta tag with property og:image
$image = $scraper->getSelector('meta[property=og:image]|content')->first(); 

// Get all paragraph innerHtml as an array
$links = $scraper->getSelector('p')->all();
 
 // Get values from the page via regex
$author = $scraper->getRegex('~"user"\:"(.*)"~')->first();

// Get JSON data
$author = WebScraper::http()
    ->from('https://example.com/page.json')
    ->get()
    ->getJson('user.name')
    ->first();

// Get title from a javascript rendered page
$title = WebScraper::api()
    ->from('https://example.com')
    ->get()
    ->getSelector('title')
    ->first();
```

## Installation

```shell
composer require jez500/web-scraper-for-laravel
```

## Author
[Jeremy Graham](https://github.com/jez500)

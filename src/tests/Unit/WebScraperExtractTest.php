<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;
use Jez500\WebScraperForLaravel\Facades\WebScraper;
use Jez500\WebScraperForLaravel\WebScraperExtract;

class WebScraperExtractTest extends WebScraperTest
{
    protected string $scraperName = ScraperServicesEnum::Extract->value;

    protected string $expectedClass = WebScraperExtract::class;

    protected string $mockResponseFile = 'extract-response.json';

    public function test_can_get_body(): void
    {
        $body = $this->getScraper()->from('https://example.com/')->get()->getBody();
        $this->assertSame(data_get(json_decode(parent::getMockResponse(), true), 'content'), $body);
    }

    public function test_factory_returns_extract_instance(): void
    {
        $scraper = WebScraper::extract();
        $this->assertInstanceOf(WebScraperExtract::class, $scraper);
    }

    public function test_can_set_and_get_extract_base_url(): void
    {
        $scraper = new WebScraperExtract;
        $baseUrl = 'https://my-extract-host';

        $scraper->setExtractApiBaseUrl($baseUrl);

        Http::fake([
            'my-extract-host/*' => Http::response('{"content": "test-content"}', 200),
        ]);

        $this->assertSame($baseUrl.'/parser', $scraper->getExtractApiBaseUrl());
        $this->assertSame('test-content', $scraper->from('https://example.com')->get()->getBody());
    }

    public function test_request_body_contains_url_and_headers(): void
    {
        $scraper = new WebScraperExtract;
        $scraper->setUrl('https://example.com');

        $body = $scraper->getRequestBody();

        $this->assertSame('https://example.com', $body['url']);
        $this->assertArrayHasKey('options', $body);
        $this->assertArrayHasKey('headers', $body['options']);
        $this->assertArrayHasKey('User-Agent', $body['options']['headers']);
        $this->assertArrayHasKey('Accept', $body['options']['headers']);
    }

    public function test_user_options_headers_win_over_defaults(): void
    {
        $scraper = new WebScraperExtract;
        $scraper->setUrl('https://example.com');
        $scraper->setOptions(['headers' => ['User-Agent' => 'MyCustomAgent/1.0']]);

        $body = $scraper->getRequestBody();

        $this->assertSame('MyCustomAgent/1.0', $body['options']['headers']['User-Agent']);
    }

    public function test_cookies_appear_in_options_headers(): void
    {
        $scraper = new WebScraperExtract;
        $scraper->setUrl('https://example.com');
        $cookies = 'session=abc123; token=xyz';
        $scraper->setCookies($cookies);

        $body = $scraper->getRequestBody();

        $this->assertArrayHasKey('Cookie', $body['options']['headers']);
        $this->assertSame($cookies, $body['options']['headers']['Cookie']);
    }

    public function test_missing_content_field_returns_empty_string(): void
    {
        $response = '{"title": "Example", "author": null}'; // missing content field entirely
        Http::fake([
            'extract:3000/*' => Http::response($response, 200),
        ]);

        $scraper = WebScraper::extract()->setUseCache(false);
        $body = $scraper->from('https://example.com')->get()->getBody();

        $this->assertSame('', $body);
    }

    public function test_can_resolve_via_driver_string(): void
    {
        Http::fake([
            'extract:3000/*' => Http::response($this->getMockResponse()),
        ]);

        $scraper = WebScraper::driver('extract')->setUseCache(false);
        $this->assertInstanceOf(WebScraperExtract::class, $scraper);
    }

    protected function setupMocks(): void
    {
        Http::fake([
            'extract:3000/*' => Http::response($this->getMockResponse()),
        ]);
    }
}

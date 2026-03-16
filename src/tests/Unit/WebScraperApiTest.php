<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;
use Jez500\WebScraperForLaravel\Facades\WebScraper;
use Jez500\WebScraperForLaravel\WebScraperApi;

class WebScraperApiTest extends WebScraperTest
{
    protected string $scraperName = ScraperServicesEnum::Api->value;

    protected string $expectedClass = WebScraperApi::class;

    protected string $mockResponseFile = 'api-response.json';

    public function test_can_get_body()
    {
        $body = $this->getScraper()->from('https://example.com/')->get()->getBody();
        $this->assertSame(data_get(json_decode(parent::getMockResponse()), 'fullContent'), $body);
    }

    public function test_can_set_base_url()
    {
        $scraper = WebScraper::api();
        $baseUrl = 'https://test-scraper-host';

        $scraper->setScraperApiBaseUrl($baseUrl); // @phpstan-ignore-line

        Http::fake([
            'test-scraper-host/*' => Http::response('{"fullContent": "test"}', 200),
        ]);

        $this->assertSame($baseUrl.'/api/article', $scraper->getScraperApiBaseUrl()); // @phpstan-ignore-line
        $this->assertSame('test', $scraper->from('http://foo.bar')->get()->getBody());
    }

    public function test_can_set_cookies()
    {
        $scraper = new WebScraperApi;
        $cookies = 'cookie1=value1; cookie2=value2';
        $scraper->setCookies($cookies);
        $reqParams = $scraper->getRequestParams();
        $this->assertArrayHasKey('extra-http-headers', $reqParams);
        $this->assertSame($reqParams['extra-http-headers'], "Cookie:$cookies");
    }

    public function test_cookies_dont_override_extra_headers()
    {
        $scraper = new WebScraperApi;
        $cookies = 'cookie1=value1; cookie2=value2';
        $scraper->setOptions(['extra-http-headers' => 'X-Test-Header: test-value']);
        $scraper->setCookies($cookies);
        $reqParams = $scraper->getRequestParams();
        $this->assertSame($reqParams['extra-http-headers'], 'X-Test-Header: test-value');
    }

    protected function setupMocks(): void
    {
        Http::fake([
            'scraper:3000/*' => Http::response($this->getMockResponse()),
        ]);
    }
}

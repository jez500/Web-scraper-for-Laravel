<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;
use Jez500\WebScraperForLaravel\WebScraperApi;
use Jez500\WebScraperForLaravel\Facades\WebScraper;

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

         $scraper->setScraperApiBaseUrl($baseUrl);

         Http::fake([
             'test-scraper-host/*' => Http::response('{"fullContent": "test"}', 200),
         ]);

         $this->assertSame($baseUrl.'/api/article', $scraper->getScraperApiBaseUrl());
         $this->assertSame('test', $scraper->from('http://foo.bar')->get()->getBody());
     }

    protected function setupMocks(): void
    {
        Http::fake([
            'scraper:3000/*' => Http::response($this->getMockResponse()),
        ]);
    }
}

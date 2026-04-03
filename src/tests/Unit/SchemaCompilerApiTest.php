<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;
use Jez500\WebScraperForLaravel\WebScraperApi;

class SchemaCompilerApiTest extends SchemaCompilerTest
{
    protected string $scraperName = ScraperServicesEnum::Api->value;

    protected string $expectedClass = WebScraperApi::class;

    protected string $mockResponseFile = 'api-response.json';

    public function test_can_get_body()
    {
        $body = $this->getScraper()->from('https://example.com/')->get()->getBody();
        $this->assertSame(data_get(json_decode(parent::getMockResponse(), true), 'fullContent'), $body);
    }

    protected function setupMocks(): void
    {
        Http::fake([
            'scraper:3000/*' => Http::response($this->getMockResponse()),
        ]);
    }
}

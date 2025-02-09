<?php

namespace Jez500\WebScraperForLaravel\tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;
use Jez500\WebScraperForLaravel\Facades\WebScraper;
use Jez500\WebScraperForLaravel\WebScraperHttp;
use Jez500\WebScraperForLaravel\WebScraperInterface;
use Jez500\WebScraperForLaravel\WebScraperServiceProvider;
use Orchestra\Testbench\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class WebScraperTest extends TestCase
{
    use WithFaker;

    protected string $scraperName = ScraperServicesEnum::Http->value;

    protected string $expectedClass = WebScraperHttp::class;

    protected string $mockResponseFile = 'http-response.html';

    protected function getPackageProviders($app): array
    {
        return [
            WebScraperServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->setupMocks();
    }

    public function test_can_set_url()
    {
        $scraper = $this->getScraper();
        $scraper->setUrl('https://example.com');
        $this->assertEquals('https://example.com', $scraper->getUrl());
    }

    public function test_can_set_use_cache()
    {
        $scraper = $this->getScraper();
        $scraper->setUseCache(false);
        $this->assertFalse($scraper->getUseCache());
    }

    public function test_can_set_cache_ttl()
    {
        $scraper = $this->getScraper();
        $scraper->setCacheMinsTtl(60);
        $this->assertEquals(60, $scraper->getCacheMinsTtl());
    }

    public function test_can_build_headers()
    {
        $scraper = $this->getScraper();
        $headers = $scraper->buildHeaders();
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Accept-Language', $headers);
        $this->assertArrayHasKey('Accept-Encoding', $headers);
    }

    public function test_can_get_body()
    {
        $body = $this->getScraper()->from('https://example.com/')->get()->getBody();
        $this->assertSame($this->getMockResponse(), $body);
    }

    public function test_can_get_dom()
    {
        $crawler = $this->getScraper()->from('https://example.com/')->get()->getDom();
        $this->assertInstanceOf(Crawler::class, $crawler);
    }

    public function test_can_get_selector()
    {
        $result = $this->getScraper()->from('https://example.com/')->get()->getSelector('title');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame('Example Domain', $result->first());
    }

    public function test_can_get_selector_via_callback()
    {
        $result = $this->getScraper()->from('https://example.com/')
            ->get()
            ->getSelector('title', fn (Crawler $node) => $node->text());

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame('Example Domain', $result->first());
    }

    public function test_can_get_regex()
    {
        $result = $this->getScraper()->from('https://example.com/')
            ->get()
            ->getRegex('~<title>(.*)</title>~');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame('Example Domain', $result->first());
    }

    protected function getScraper(): WebScraperInterface
    {
        return WebScraper::make($this->scraperName)->setUseCache(false);
    }

    protected function getMockResponse(): string
    {
        return file_get_contents(__DIR__.'/../Mocks/'.$this->mockResponseFile);
    }

    protected function setupMocks(): void
    {
        Http::fake([
            'example.com/*' => Http::response($this->getMockResponse()),
        ]);
    }
}

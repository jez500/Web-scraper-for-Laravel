<?php

namespace Jez500\WebScraperForLaravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Drivers\ApiDriver;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;

class WebScraperApi extends AbstractWebScraper
{
    public static string $scraperApiUrl = 'http://scraper:3000/api/article';

    protected array $defaultRequestParams = [
        'sleep' => 2000,
        'full-content' => true,
        'device' => 'Desktop Chrome',
        'wait-until' => 'networkidle',
        'timeout' => 30000,
        'cache' => false, // We cache in this app.
    ];

    public function __construct(?WebScraperDriverInterface $driver = null)
    {
        parent::__construct();

        $this->setDriver($driver ?? resolve(ApiDriver::class));
    }

    public function setScraperApiBaseUrl(string $scraperApiBaseUrl): self
    {
        static::$scraperApiUrl = trim($scraperApiBaseUrl, '/').'/api/article';

        return $this;
    }

    public function getScraperApiBaseUrl(): string
    {
        return static::$scraperApiUrl;
    }

    public function getRequest(): PendingRequest
    {
        return Http::withHeaders([])
            ->connectTimeout($this->getConnectTimeout())
            ->timeout($this->getRequestTimeout());
    }

    public function getRequestParams(): array
    {
        $defaultParams = $this->defaultRequestParams;
        $defaultParams['timeout'] = $this->getRequestTimeout() * 1000; // Convert to milliseconds

        if ($this->cookies) {
            $defaultParams['extra-http-headers'] = "Cookie:{$this->cookies}";
        }

        return array_merge(['url' => $this->url], $defaultParams, $this->getOptions());
    }
}

<?php

namespace Jez500\WebScraperForLaravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Drivers\ExtractDriver;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;

class WebScraperExtract extends AbstractWebScraper
{
    public static string $extractApiUrl = 'http://extract:3000/parser';

    public function __construct(?WebScraperDriverInterface $driver = null)
    {
        parent::__construct();

        $this->setDriver($driver ?? resolve(ExtractDriver::class));
    }

    public function setExtractApiBaseUrl(string $url): self
    {
        static::$extractApiUrl = rtrim($url, '/').'/parser';

        return $this;
    }

    public function getExtractApiBaseUrl(): string
    {
        return static::$extractApiUrl;
    }

    public function getRequest(): PendingRequest
    {
        return Http::withHeaders([])
            ->connectTimeout($this->getConnectTimeout())
            ->timeout($this->getRequestTimeout());
    }

    public function getRequestBody(): array
    {
        $defaultHeaders = $this->buildHeaders();
        $userHeaders = data_get($this->getOptions(), 'headers', []);

        return [
            'url' => $this->url,
            'options' => [
                'headers' => array_merge($defaultHeaders, $userHeaders),
            ],
        ];
    }
}

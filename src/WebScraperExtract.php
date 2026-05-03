<?php

namespace Jez500\WebScraperForLaravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Drivers\ExtractDriver;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;

class WebScraperExtract extends AbstractWebScraper
{
    public static string $extractApiUrl = 'http://extract:3000/parser';
    private string $instanceApiUrl;

    public function __construct(?WebScraperDriverInterface $driver = null)
    {
        parent::__construct();

        $this->instanceApiUrl = static::$extractApiUrl;
        $this->setDriver($driver ?? resolve(ExtractDriver::class));
    }

    public function setExtractApiBaseUrl(string $url, bool $appendParser = false): self
    {
        $this->instanceApiUrl = rtrim($url, '/');
        if ($appendParser) {
            $this->instanceApiUrl .= '/parser';
        }

        return $this;
    }

    public function getExtractApiBaseUrl(): string
    {
        return $this->instanceApiUrl;
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
        unset($defaultHeaders['Accept-Encoding']);
        $userHeaders = data_get($this->getOptions(), 'headers', []);

        return [
            'url' => $this->url,
            'options' => [
                'headers' => array_merge($defaultHeaders, $userHeaders),
            ],
        ];
    }
}

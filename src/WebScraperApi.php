<?php

namespace Jez500\WebScraperForLaravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class WebScraperApi extends AbstractWebScraper
{
    protected string $scraperApiUrl = 'http://scraper:3000/api/article';

    protected array $defaultRequestParams = [
        'sleep' => 2000,
        'full-content' => true,
        'device' => 'Desktop Chrome',
        'wait-until' => 'networkidle',
        'timeout' => 120000,
        'cache' => false, // We cache in this app.
    ];

    public function setScraperApiBaseUrl(string $scraperApiBaseUrl): self
    {
        $this->scraperApiUrl = trim($scraperApiBaseUrl, '/').'/api/article';

        return $this;
    }

    public function getRequest(): PendingRequest
    {
        return Http::withHeaders([])->timeout($this->scraperRequestTimeout);
    }

    public function get(): self
    {
        $request = function () {
            try {
                $result = $this->getRequest()
                    ->get($this->scraperApiUrl, $this->getRequestParams());

                $json = $result->json();

                $fullContent = data_get($json, 'fullContent', '');

                if (! $fullContent) {
                    $this->errors[] = [
                        'request_url' => $this->scraperApiUrl,
                        'request_params' => $this->getRequestParams(),
                        'message' => 'No content found',
                        'code' => Response::HTTP_NO_CONTENT,
                        'response' => $json,
                    ];
                }

                return $fullContent;
            } catch (ConnectionException $e) {
                logger()->error($e->getMessage());
                $this->errors[] = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
            }

            return '';
        };

        $this->body = $this->useCache
            ? cache()->remember(
                $this->getCacheKey($this->url),
                now()->addMinutes($this->cacheMinsTtl),
                $request
            )
            : $request();

        return $this;
    }

    public function getRequestParams(): array
    {
        return array_merge(['url' => $this->url], $this->defaultRequestParams, $this->getOptions());
    }
}

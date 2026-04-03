<?php

namespace Jez500\WebScraperForLaravel\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Jez500\WebScraperForLaravel\AbstractWebScraper;
use Jez500\WebScraperForLaravel\WebScraperApi;
use Symfony\Component\HttpFoundation\Response;

class ApiDriver implements WebScraperDriverInterface
{
    public function fetch(AbstractWebScraper $scraper): string
    {
        if (! $scraper instanceof WebScraperApi) {
            throw new InvalidArgumentException('The API driver can only be used with WebScraperApi instances.');
        }

        try {
            $result = $this->getRequest($scraper)
                ->get($scraper->getScraperApiBaseUrl(), $scraper->getRequestParams());

            $json = $result->json();
            $fullContent = data_get($json, 'fullContent', '');

            if (! $fullContent) {
                $scraper->addError([
                    'request_url' => $scraper->getScraperApiBaseUrl(),
                    'request_params' => $scraper->getRequestParams(),
                    'message' => 'No content found',
                    'code' => Response::HTTP_NO_CONTENT,
                    'response' => $json,
                ]);
            }

            return $fullContent;
        } catch (ConnectionException $e) {
            logger()->error($e->getMessage());
            $scraper->addError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return '';
    }

    protected function getRequest(WebScraperApi $scraper)
    {
        return Http::withHeaders([])
            ->connectTimeout($scraper->getConnectTimeout())
            ->timeout($scraper->getRequestTimeout());
    }
}

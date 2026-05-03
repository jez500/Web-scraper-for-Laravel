<?php

namespace Jez500\WebScraperForLaravel\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Jez500\WebScraperForLaravel\AbstractWebScraper;
use Jez500\WebScraperForLaravel\WebScraperExtract;
use Symfony\Component\HttpFoundation\Response;

class ExtractDriver implements WebScraperDriverInterface
{
    public function fetch(AbstractWebScraper $scraper): string
    {
        if (! $scraper instanceof WebScraperExtract) {
            throw new InvalidArgumentException('The Extract driver can only be used with WebScraperExtract instances.');
        }

        try {
            $result = $this->getRequest($scraper)
                ->post($scraper->getExtractApiBaseUrl(), $scraper->getRequestBody());

            $json = $result->json();
            $content = data_get($json, 'content', '');

            if (! $content) {
                $requestBody = $scraper->getRequestBody();
                $sanitizedBody = $this->sanitizeRequestBody($requestBody);
                $scraper->addError([
                    'request_url' => $scraper->getExtractApiBaseUrl(),
                    'request_body' => $sanitizedBody,
                    'message' => 'No content found',
                    'code' => Response::HTTP_NO_CONTENT,
                    'response' => $json,
                ]);
            }

            return (string) $content;
        } catch (ConnectionException $e) {
            logger()->error($e->getMessage());
            $scraper->addError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return '';
    }

    protected function getRequest(WebScraperExtract $scraper)
    {
        return Http::withHeaders(['Content-Type' => 'application/json'])
            ->connectTimeout($scraper->getConnectTimeout())
            ->timeout($scraper->getRequestTimeout());
    }

    private function sanitizeRequestBody(array $requestBody): array
    {
        $sanitized = $requestBody;
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token', 'x-access-token'];

        if (isset($sanitized['options']['headers']) && is_array($sanitized['options']['headers'])) {
            $headers = [];
            foreach ($sanitized['options']['headers'] as $key => $value) {
                $lowerKey = strtolower($key);
                if (in_array($lowerKey, $sensitiveHeaders, true)) {
                    $headers[$key] = '[REDACTED]';
                } else {
                    $headers[$key] = $value;
                }
            }
            $sanitized['options']['headers'] = $headers;
        }

        return $sanitized;
    }
}

<?php

namespace Jez500\WebScraperForLaravel;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WebScraperHttp extends AbstractWebScraper
{
    public function getRequest(): PendingRequest
    {
        return Http::withHeaders($this->buildHeaders())->timeout($this->scraperRequestTimeout);
    }

    public function get(): self
    {
        $request = function () {
            try {
                return $this->getRequest()->get($this->url)->body();
            } catch (Exception $e) {
                $this->errors[] = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
                logger()->error($e->getMessage());
            }

            return '';
        };

        $this->body = $this->useCache === true
            ? Cache::remember(
                $this->getCacheKey($this->url),
                now()->addMinutes($this->cacheMinsTtl),
                fn () => $request()
            )
            : $request();

        return $this;
    }
}

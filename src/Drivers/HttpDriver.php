<?php

namespace Jez500\WebScraperForLaravel\Drivers;

use Exception;
use Jez500\WebScraperForLaravel\AbstractWebScraper;
use Jez500\WebScraperForLaravel\WebScraperHttp;

class HttpDriver implements WebScraperDriverInterface
{
    public function fetch(AbstractWebScraper $scraper): string
    {
        if (! $scraper instanceof WebScraperHttp) {
            throw new Exception('The HTTP driver can only be used with WebScraperHttp instances.');
        }

        try {
            return $scraper->getRequest()->get($scraper->getUrl())->body();
        } catch (Exception $e) {
            $scraper->addError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            logger()->error($e->getMessage());
        }

        return '';
    }
}

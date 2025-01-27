<?php

namespace Jez500\WebScraperForLaravel;

use InvalidArgumentException;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;

class WebScraperFactory
{
    public function http(): WebScraperInterface
    {
        return resolve(WebScraperHttp::class);
    }

    public function api(): WebScraperInterface
    {
        return resolve(WebScraperApi::class);
    }

    public function fake(): WebScraperInterface
    {
        return resolve(WebScraperFake::class);
    }

    public function make(string $type): WebScraperInterface
    {
        if (! in_array($type, ScraperServicesEnum::values())) {
            throw new InvalidArgumentException("Invalid WebScraper type: {$type}");
        }

        return call_user_func([$this, $type]);
    }
}

<?php

namespace Jez500\WebScraperForLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use Jez500\WebScraperForLaravel\WebScraperInterface;

/**
 * @method static WebScraperInterface http()
 * @method static WebScraperInterface api()
 * @method static WebScraperInterface make(string $type)
 */
class WebScraper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'web_scraper';
    }
}

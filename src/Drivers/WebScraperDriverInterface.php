<?php

namespace Jez500\WebScraperForLaravel\Drivers;

use Jez500\WebScraperForLaravel\AbstractWebScraper;

interface WebScraperDriverInterface
{
    public function fetch(AbstractWebScraper $scraper): string;
}

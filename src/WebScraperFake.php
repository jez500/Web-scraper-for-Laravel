<?php

namespace Jez500\WebScraperForLaravel;

class WebScraperFake extends AbstractWebScraper
{
    public function get(): self
    {
        return $this;
    }
}

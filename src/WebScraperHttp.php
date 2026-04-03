<?php

namespace Jez500\WebScraperForLaravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Drivers\HttpDriver;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;

class WebScraperHttp extends AbstractWebScraper
{
    public function __construct(?WebScraperDriverInterface $driver = null)
    {
        parent::__construct();

        $this->setDriver($driver ?? resolve(HttpDriver::class));
    }

    public function getRequest(): PendingRequest
    {
        return Http::withHeaders($this->buildHeaders())->timeout($this->scraperRequestTimeout);
    }
}

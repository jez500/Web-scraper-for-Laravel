<?php

namespace Jez500\WebScraperForLaravel;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

interface WebScraperInterface
{
    public function from(string $url): self;

    public function getRequest(): PendingRequest;

    public function get(): self;

    public function buildHeaders(): array;

    public function setUrl(string $url): self;

    public function getUrl(): string;

    public function setUseCache(bool $useCache): self;

    public function getOptions(): array;

    public function setOptions(array $options): self;

    public function getUseCache(): bool;

    public function setCacheMinsTtl(int $cacheMinsTtl): self;

    public function getCacheMinsTtl(): int;

    public function setConnectTimeout(int $scraperConnectTimeout): self;

    public function getConnectTimeout(): int;

    public function setRequestTimeout(int $scraperRequestTimeout): self;

    public function getRequestTimeout(): int;

    public function getDom(): Crawler;

    public function getSelector(string $selector, string|Closure $nodeContent = 'text', array $nodeContentArgs = []): Collection;

    public function getXpath(string $xpath, string|Closure $nodeContent = 'text', array $nodeContentArgs = []): Collection;

    public function getJson(string $path): Collection;

    public function getRegex(string $regex): Collection;

    public function getErrors(): array;

    public function getBody(): string;

    public function setBody(string $body): self;
}

<?php

namespace Jez500\WebScraperForLaravel;

use Closure;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;
use Jez500\WebScraperForLaravel\Dto\FieldExtractionDto;
use Jez500\WebScraperForLaravel\Dto\ScrapeSchemaDto;
use Jez500\WebScraperForLaravel\Exceptions\DomSelectorException;
use Jez500\WebScraperForLaravel\Schema\SchemaCompiler;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractWebScraper implements WebScraperInterface
{
    protected WebScraperDriverInterface $driver;

    protected UserAgentGenerator $userAgentGenerator;

    protected bool $useCache = true;

    protected int $cacheMinsTtl = 720;

    protected int $scraperRequestTimeout = 30;

    protected int $scraperConnectTimeout = 30;

    protected string $cacheKey = 'web_scraper:';

    protected string $body = '';

    protected ?string $url = null;

    protected array $options = [];

    protected string $cookies = '';

    protected array $errors = [];

    public function __construct()
    {
        $this->userAgentGenerator = new UserAgentGenerator;
    }

    public function setDriver(WebScraperDriverInterface $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function from(string $url): self
    {
        $self = clone $this;
        $self->setUrl($url);
        $self->body = '';
        $self->errors = [];

        return $self;
    }

    public function buildHeaders(): array
    {
        $headers = [
            'User-Agent' => $this->userAgentGenerator->generate(),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
        ];

        if ($this->cookies) {
            $headers['Cookie'] = $this->cookies;
        }

        return $headers;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setCookies(string $cookies): self
    {
        $this->cookies = $cookies;

        return $this;
    }

    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    public function getUseCache(): bool
    {
        return $this->useCache;
    }

    public function setCacheMinsTtl(int $cacheMinsTtl): self
    {
        $this->cacheMinsTtl = $cacheMinsTtl;

        return $this;
    }

    public function getCacheMinsTtl(): int
    {
        return $this->cacheMinsTtl;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getRequestTimeout(): int
    {
        return $this->scraperRequestTimeout;
    }

    public function setRequestTimeout(int $scraperRequestTimeout): self
    {
        $this->scraperRequestTimeout = $scraperRequestTimeout;

        return $this;
    }

    public function getConnectTimeout(): int
    {
        return $this->scraperConnectTimeout;
    }

    public function setConnectTimeout(int $scraperConnectTimeout): self
    {
        $this->scraperConnectTimeout = $scraperConnectTimeout;

        return $this;
    }

    public function getRequest(): PendingRequest
    {
        return Http::connectTimeout($this->getConnectTimeout())
            ->timeout($this->getRequestTimeout());
    }

    public function get(): self
    {
        if (! isset($this->driver)) {
            throw new Exception('No WebScraper driver has been configured.');
        }

        $request = fn () => $this->driver->fetch($this);

        $this->body = $this->useCache === true
            ? Cache::remember(
                $this->getCacheKey($this->url),
                now()->addMinutes($this->cacheMinsTtl),
                $request
            )
            : $request();

        return $this;
    }

    public function getDom(): Crawler
    {
        return new Crawler($this->body);
    }

    public function getSelector(string $selector, string|Closure $nodeContent = 'text', array $nodeContentArgs = []): Collection
    {
        if (! $nodeContent instanceof Closure && ! in_array($nodeContent, ['text', 'html', 'attr'])) {
            throw new Exception('Invalid node content type');
        }

        try {
            $items = $this->getDom()
                ->filter($this->escapeSelector($selector))
                ->each(function (Crawler $node) use ($nodeContent, $nodeContentArgs) {
                    return $nodeContent instanceof Closure
                        ? $nodeContent($node)
                        : call_user_func_array([$node, $nodeContent], $nodeContentArgs);
                });
        } catch (Exception $e) {
            throw new DomSelectorException($e->getMessage());
        }

        return collect($items);
    }

    public function getXpath(string $xpath, string|Closure $nodeContent = 'text', array $nodeContentArgs = []): Collection
    {
        if (! $nodeContent instanceof Closure && ! in_array($nodeContent, ['text', 'html', 'attr'])) {
            throw new Exception('Invalid node content type');
        }

        try {
            $items = $this->getDom()
                ->filterXPath($xpath)
                ->each(function (Crawler $node) use ($nodeContent, $nodeContentArgs) {
                    return $nodeContent instanceof Closure
                        ? $nodeContent($node)
                        : call_user_func_array([$node, $nodeContent], $nodeContentArgs);
                });
        } catch (\InvalidArgumentException $e) {
            throw new DomSelectorException('Invalid XPath expression: '.$e->getMessage());
        } catch (Exception $e) {
            throw new DomSelectorException('Error processing XPath result: '.$e->getMessage());
        }

        return collect($items);
    }

    public function getJson(string $path): Collection
    {
        $json = json_decode($this->body, true);

        if (is_null($json)) {
            return collect();
        }

        $value = data_get($json, $path, []);

        return collect(Arr::wrap($value));
    }

    public function getRegex(string $regex): Collection
    {
        preg_match_all($regex, $this->body, $matches);

        return collect($matches[1] ?? []);
    }

    public function getSchemaOrg(): Collection
    {
        return $this->getSelector('script[type="application/ld+json"]')
            ->map(fn ($json) => json_decode($json, true))
            ->filter()
            ->values();
    }

    public function fromDto(FieldExtractionDto|ScrapeSchemaDto|array|string $schema): Collection
    {
        return (new SchemaCompiler($this))->compile($schema);
    }

    /**
     * Escape selector for Crawler, this will probably need more refinement
     * over time.
     */
    protected function escapeSelector(string $selector): string
    {
        $selector = str_replace(':', '\:', $selector);

        return $selector;
    }

    protected function getCacheKey(string $url): string
    {
        $driverKey = isset($this->driver)
            ? class_basename($this->driver)
            : class_basename($this);

        return $this->cacheKey.$driverKey.':'.md5($url);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(array $error): self
    {
        $this->errors[] = $error;

        return $this;
    }
}

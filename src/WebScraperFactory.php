<?php

namespace Jez500\WebScraperForLaravel;

use Closure;
use InvalidArgumentException;
use Jez500\WebScraperForLaravel\Drivers\WebScraperDriverInterface;
use Jez500\WebScraperForLaravel\Enums\ScraperServicesEnum;

class WebScraperFactory
{
    protected array $extensions = [];

    public function http(): WebScraperInterface
    {
        return $this->driver(ScraperServicesEnum::Http->value);
    }

    public function api(): WebScraperInterface
    {
        return $this->driver(ScraperServicesEnum::Api->value);
    }

    public function extract(): WebScraperInterface
    {
        return $this->driver(ScraperServicesEnum::Extract->value);
    }

    public function fake(): WebScraperInterface
    {
        return resolve(WebScraperFake::class);
    }

    public function extend(string $driver, Closure|callable|string $resolver): self
    {
        $this->extensions[$driver] = $resolver;

        return $this;
    }

    public function driver(string $driver): WebScraperInterface
    {
        return match ($driver) {
            ScraperServicesEnum::Http->value => resolve(WebScraperHttp::class),
            ScraperServicesEnum::Api->value => resolve(WebScraperApi::class),
            ScraperServicesEnum::Extract->value => resolve(WebScraperExtract::class),
            ScraperServicesEnum::Fake->value => resolve(WebScraperFake::class),
            default => $this->makeCustomDriver($driver),
        };
    }

    public function make(string $type): WebScraperInterface
    {
        return $this->driver($type);
    }

    protected function makeCustomDriver(string $type): WebScraperInterface
    {
        $driver = $this->resolveCustomDriver($type);

        return resolve(WebScraper::class)->setDriver($driver);
    }

    protected function resolveCustomDriver(string $type): WebScraperDriverInterface
    {
        $resolver = $this->extensions[$type] ?? $type;

        if ($resolver instanceof Closure || is_callable($resolver)) {
            $driver = $resolver();
        } else {
            if (! app()->bound($resolver) && ! class_exists($resolver)) {
                throw new InvalidArgumentException("Invalid WebScraper driver: {$type}");
            }

            $driver = resolve($resolver);
        }

        if (! $driver instanceof WebScraperDriverInterface) {
            throw new InvalidArgumentException("Invalid WebScraper driver: {$type}");
        }

        return $driver;
    }
}

<?php

namespace Jez500\WebScraperForLaravel;

use Illuminate\Support\ServiceProvider;

class WebScraperServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('web_scraper', function () {
            return new WebScraperFactory;
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/tests.php');
    }
}

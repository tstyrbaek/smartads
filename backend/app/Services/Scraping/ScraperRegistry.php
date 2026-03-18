<?php

namespace App\Services\Scraping;

use App\Services\Scraping\Sites\BilbasenSiteScraper;
use App\Services\Scraping\Sites\BmcLeasingSiteScraper;
use App\Services\Scraping\Sites\BoesenbaekSiteScraper;
use App\Services\Scraping\Sites\BoligsidenSiteScraper;
use App\Services\Scraping\Sites\CykelcenterMidtjyllandSiteScraper;
use App\Services\Scraping\Sites\DanboligSiteScraper;
use App\Services\Scraping\Sites\EdcSiteScraper;
use App\Services\Scraping\Sites\ElsalgSiteScraper;
use App\Services\Scraping\Sites\HjemmehosSiteScraper;
use App\Services\Scraping\Sites\HuniqueSiteScraper;
use App\Services\Scraping\Sites\SebilerSiteScraper;

class ScraperRegistry
{
    public function all(): array
    {
        return [
            new BoligsidenSiteScraper(),
            new EdcSiteScraper(),
            new DanboligSiteScraper(),
            new BmcLeasingSiteScraper(),
            new BoesenbaekSiteScraper(),
            new HjemmehosSiteScraper(),
            new ElsalgSiteScraper(),
            new SebilerSiteScraper(),
            new BilbasenSiteScraper(),
            new CykelcenterMidtjyllandSiteScraper(),
            new HuniqueSiteScraper(),
        ];
    }

    public function forUrl(string $url): ?SiteScraperInterface
    {
        foreach ($this->all() as $scraper) {
            if ($scraper->supports($url)) {
                return $scraper;
            }
        }
        return null;
    }
}

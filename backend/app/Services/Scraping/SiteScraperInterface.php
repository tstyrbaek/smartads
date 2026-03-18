<?php

namespace App\Services\Scraping;

interface SiteScraperInterface
{
    public function supports(string $url): bool;

    /**
     * @return array<string, mixed>
     */
    public function scrape(string $url): array;
}

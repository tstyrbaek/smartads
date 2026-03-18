<?php

declare(strict_types=1);

interface SiteScraperInterface
{
    public function supports(string $url): bool;

    /**
     * @return array<string, mixed>
     */
    public function scrape(string $url): array;
}

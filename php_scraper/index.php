<?php

declare(strict_types=1);

require __DIR__ . '/src/Scraper.php';
require __DIR__ . '/scrapers/SiteScraperInterface.php';
require __DIR__ . '/scrapers/boligsiden.php';
require __DIR__ . '/scrapers/edc.php';
require __DIR__ . '/scrapers/danbolig.php';
require __DIR__ . '/scrapers/bmcleasing.php';
require __DIR__ . '/scrapers/boesenbaek.php';
require __DIR__ . '/scrapers/hjemmehos.php';
require __DIR__ . '/scrapers/elsalg.php';
require __DIR__ . '/scrapers/sebiler.php';
require __DIR__ . '/scrapers/bilbasen.php';
require __DIR__ . '/scrapers/cykelcentermidtjylland.php';
require __DIR__ . '/scrapers/hunique.php';

$url = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? null;
} else {
    $url = $_GET['url'] ?? null;
}

$url = is_string($url) ? trim($url) : '';

$data = null;
$error = null;

if ($url !== '') {
    try {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL');
        }

        $siteScrapers = [
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

        $selected = null;
        foreach ($siteScrapers as $siteScraper) {
            if ($siteScraper->supports($url)) {
                $selected = $siteScraper;
                break;
            }
        }
        if (!$selected instanceof SiteScraperInterface) {
            throw new InvalidArgumentException('Unsupported domain');
        }

        $data = $selected->scrape($url);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Scraper</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0b1020; color: #eef2ff; margin: 0; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 32px 16px 64px; }
        .card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 16px; }
        h1 { font-size: 20px; margin: 0 0 12px; }
        form { display: grid; grid-template-columns: 1fr auto; gap: 12px; }
        input[type="url"] { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.18); background: rgba(0,0,0,0.2); color: #eef2ff; }
        button { padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.18); background: #4f46e5; color: white; cursor: pointer; font-weight: 600; }
        button:hover { background: #4338ca; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
        pre { margin: 0; padding: 12px; border-radius: 12px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.12); overflow: auto; }
        .error { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.35); }
        .images { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
        .images a { display: block; text-decoration: none; color: inherit; }
        .images img { width: 100%; height: 140px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12); background: rgba(0,0,0,0.2); }
        .muted { color: rgba(238,242,255,0.7); font-size: 13px; margin-top: 10px; }
        @media (max-width: 640px) { form { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Scrape URL</h1>
        <form method="post" action="">
            <input type="url" name="url" required placeholder="Indsæt URL..." value="<?php echo $escapedUrl; ?>">
            <button type="submit">Scrape</button>
        </form>
        <div class="muted">Resultatet bliver hentet server-side via PHP (cURL) og udtrukket via XPath.</div>
    </div>

    <div class="grid">
        <?php if ($error !== null): ?>
            <div class="card error">
                <strong>Fejl:</strong>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>

        <?php if (is_array($data)): ?>
            <?php if (is_string($data['source'] ?? null) && $data['source'] !== ''): ?>
                <div class="card">
                    <h1>Scraper</h1>
                    <div><?php echo htmlspecialchars((string) $data['source'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <div class="card">
                <h1>Data</h1>
                <pre><?php echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></pre>
            </div>

            <?php if (is_array($data['images']) && count($data['images']) > 0): ?>
                <div class="card">
                    <h1>Billeder</h1>
                    <div class="images">
                        <?php foreach ($data['images'] as $imgUrl): ?>
                            <a href="<?php echo htmlspecialchars((string) $imgUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                <img src="<?php echo htmlspecialchars((string) $imgUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

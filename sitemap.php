<?php
// ==============================================
// FILE: sitemap.php
// ==============================================

require_once 'config.php';

// Check cache
$cacheFile = CACHE_PATH . '/sitemap.xml';
$cacheTTL = SITEMAP_CACHE_TTL;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    header('Content-Type: application/xml; charset=utf-8');
    readfile($cacheFile);
    exit;
}

$db = Database::getInstance();
$urls = [];

// Homepage
$urls[] = [
    'loc' => SITE_URL . '/',
    'lastmod' => date('Y-m-d'),
    'changefreq' => 'weekly',
    'priority' => '1.0'
];

// Published Pages
$pages = $db->fetchAll("SELECT slug, updated_at FROM pages WHERE status = 'published'");
foreach ($pages as $page) {
    $urls[] = [
        'loc' => SITE_URL . '/page/' . $page['slug'],
        'lastmod' => date('Y-m-d', strtotime($page['updated_at'])),
        'changefreq' => 'monthly',
        'priority' => '0.8'
    ];
}

// Published Posts
$posts = $db->fetchAll("SELECT slug, updated_at FROM posts WHERE status = 'published'");
foreach ($posts as $post) {
    $urls[] = [
        'loc' => SITE_URL . '/blog/' . $post['slug'],
        'lastmod' => date('Y-m-d', strtotime($post['updated_at'])),
        'changefreq' => 'weekly',
        'priority' => '0.9'
    ];
}

// Published Projects
$projects = $db->fetchAll("SELECT slug, updated_at FROM github_projects WHERE status = 'published'");
foreach ($projects as $project) {
    $urls[] = [
        'loc' => SITE_URL . '/project/' . $project['slug'],
        'lastmod' => date('Y-m-d', strtotime($project['updated_at'])),
        'changefreq' => 'monthly',
        'priority' => '0.7'
    ];
}

// Static listing pages
$listingPages = [
    ['loc' => SITE_URL . '/blog', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => SITE_URL . '/projects', 'priority' => '0.7', 'changefreq' => 'daily']
];
foreach ($listingPages as $listing) {
    $listing['lastmod'] = date('Y-m-d');
    $urls[] = $listing;
}

// Generate XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $url) {
    $xml .= '  <url>' . "\n";
    $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
    if (isset($url['lastmod'])) {
        $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
    }
    if (isset($url['changefreq'])) {
        $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
    }
    if (isset($url['priority'])) {
        $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
    }
    $xml .= '  </url>' . "\n";
}

$xml .= '</urlset>';

// Save to cache
file_put_contents($cacheFile, $xml);

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
?>
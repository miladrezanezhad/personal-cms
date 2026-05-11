<?php
require_once 'config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

$seoSettings = getSEOSettings();
$robotsContent = $seoSettings['robots_txt_content'] ?? '';

if (empty($robotsContent)) {
    $robotsContent = "User-agent: *\n";
    $robotsContent .= "Allow: /\n";
    $robotsContent .= "Disallow: /admin/\n";
    $robotsContent .= "Disallow: /api/\n";
    $robotsContent .= "Disallow: /includes/\n";
    $robotsContent .= "Disallow: /cron/\n";
    $robotsContent .= "Disallow: /logs/\n";
    $robotsContent .= "Disallow: /backups/\n";
    $robotsContent .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";
}

echo $robotsContent;
?>
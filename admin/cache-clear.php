<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

$db = Database::getInstance();
$message = '';

// Handle cache clearing
if (isset($_POST['clear_github'])) {
    $db->query("TRUNCATE TABLE github_cache");
    $message = "GitHub API cache cleared.";
}

if (isset($_POST['clear_sitemap'])) {
    $sitemapCache = CACHE_PATH . '/sitemap.xml';
    if (file_exists($sitemapCache)) {
        unlink($sitemapCache);
    }
    $message = "Sitemap cache cleared.";
}

if (isset($_POST['clear_settings'])) {
    // Clear settings cache
    getSettings(null, true);
    getSEOSettings(null, true);
    $message = "Settings cache cleared.";
}

if (isset($_POST['clear_all'])) {
    $db->query("TRUNCATE TABLE github_cache");
    $sitemapCache = CACHE_PATH . '/sitemap.xml';
    if (file_exists($sitemapCache)) {
        unlink($sitemapCache);
    }
    getSettings(null, true);
    getSEOSettings(null, true);
    $message = "All caches cleared.";
}

// Get cache stats
$githubCacheCount = $db->fetchOne("SELECT COUNT(*) as count FROM github_cache")['count'] ?? 0;
$sitemapExists = file_exists(CACHE_PATH . '/sitemap.xml');

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Clear Cache</h1>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success" id="successAlert"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">GitHub API Cache</h5>
                </div>
                <div class="card-body">
                    <p>Cached items: <strong><?php echo number_format($githubCacheCount); ?></strong></p>
                    <p>Clearing this cache will force fresh data from GitHub API on next request.</p>
                    <form method="POST">
                        <button type="submit" name="clear_github" class="btn btn-warning w-100">Clear GitHub Cache</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Sitemap Cache</h5>
                </div>
                <div class="card-body">
                    <p>Status: <?php echo $sitemapExists ? '<span class="text-success">Cached</span>' : '<span class="text-muted">Not cached</span>'; ?></p>
                    <p>Sitemap is regenerated every 24 hours or when cleared.</p>
                    <form method="POST">
                        <button type="submit" name="clear_sitemap" class="btn btn-info w-100">Clear Sitemap Cache</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Settings Cache</h5>
                </div>
                <div class="card-body">
                    <p>Clear cached settings and SEO settings.</p>
                    <p>Use this after manually editing the database.</p>
                    <form method="POST">
                        <button type="submit" name="clear_settings" class="btn btn-secondary w-100">Clear Settings Cache</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">All Caches</h5>
                </div>
                <div class="card-body">
                    <p>Clear all cached data at once.</p>
                    <form method="POST" onsubmit="return confirm('Clear all caches? This may slow down the site temporarily.');">
                        <button type="submit" name="clear_all" class="btn btn-danger w-100">Clear All Caches</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Manual Cache Operations</h5>
        </div>
        <div class="card-body">
            <a href="<?php echo SITE_URL; ?>/sitemap.php" target="_blank" class="btn btn-outline-primary">Regenerate Sitemap Now</a>
            <a href="sync-manual.php" class="btn btn-outline-info ms-2">Sync GitHub Projects</a>
            <a href="settings.php" class="btn btn-outline-secondary ms-2">Edit Settings</a>
        </div>
    </div>
</div>

<script>
    setTimeout(function() {
        var alert = document.getElementById('successAlert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 3000);
</script>

<?php require_once '../templates/admin-footer.php'; ?>
<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

// Check various system configurations
$checks = [];

// PHP Version
$checks[] = [
    'name' => 'PHP Version >= 7.4',
    'status' => version_compare(PHP_VERSION, '7.4', '>='),
    'value' => PHP_VERSION,
    'note' => version_compare(PHP_VERSION, '7.4', '>=') ? 'OK' : 'Upgrade PHP to 7.4+'
];

// Database connection
try {
    $db = Database::getInstance();
    $checks[] = [
        'name' => 'Database Connection',
        'status' => true,
        'value' => 'Connected',
        'note' => 'OK'
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Database Connection',
        'status' => false,
        'value' => 'Failed',
        'note' => $e->getMessage()
    ];
}

// Required extensions
$extensions = ['pdo_mysql', 'curl', 'gd', 'json', 'session'];
foreach ($extensions as $ext) {
    $checks[] = [
        'name' => "PHP Extension: $ext",
        'status' => extension_loaded($ext),
        'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
        'note' => extension_loaded($ext) ? 'OK' : 'Install ' . $ext . ' extension'
    ];
}

// Directory permissions
$dirs = ['uploads', 'logs', 'backups', 'cache'];
foreach ($dirs as $dir) {
    $path = BASE_PATH . '/' . $dir;
    $writable = is_writable($path);
    $checks[] = [
        'name' => "Directory: /$dir",
        'status' => $writable,
        'value' => $writable ? 'Writable' : 'Not writable',
        'note' => $writable ? 'OK' : 'Set chmod 755 or 775'
    ];
}

// Site URL
$checks[] = [
    'name' => 'SITE_URL constant',
    'status' => defined('SITE_URL'),
    'value' => defined('SITE_URL') ? SITE_URL : 'Not set',
    'note' => defined('SITE_URL') ? 'OK' : 'Define SITE_URL in config.php'
];

// Display errors (should be 0 in production)
$checks[] = [
    'name' => 'display_errors setting',
    'status' => ini_get('display_errors') == 0,
    'value' => ini_get('display_errors') ? 'On' : 'Off',
    'note' => ini_get('display_errors') == 0 ? 'Good for production' : 'Set display_errors = 0 in production'
];

// HTTPS check
$checks[] = [
    'name' => 'HTTPS Enabled',
    'status' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'value' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'Yes' : 'No',
    'note' => 'Enable SSL certificate for security'
];

// GitHub token
$githubToken = getSettings('github_token');
$checks[] = [
    'name' => 'GitHub Token',
    'status' => !empty($githubToken),
    'value' => !empty($githubToken) ? 'Set' : 'Not set',
    'note' => !empty($githubToken) ? 'OK' : 'Add GitHub token for higher API limits'
];

// Admin password check
$checks[] = [
    'name' => 'Admin Password Changed',
    'status' => true,
    'value' => 'Manual check required',
    'note' => 'Ensure default admin password has been changed!'
];

// Sitemap accessibility
$sitemapUrl = SITE_URL . '/sitemap.xml';
$sitemapHeaders = @get_headers($sitemapUrl);
$checks[] = [
    'name' => 'Sitemap Accessible',
    'status' => $sitemapHeaders && strpos($sitemapHeaders[0], '200'),
    'value' => $sitemapHeaders && strpos($sitemapHeaders[0], '200') ? 'Yes' : 'No',
    'note' => 'Visit ' . $sitemapUrl . ' to verify'
];

// Robots.txt check
$robotsUrl = SITE_URL . '/robots.txt';
$robotsHeaders = @get_headers($robotsUrl);
$checks[] = [
    'name' => 'Robots.txt Accessible',
    'status' => $robotsHeaders && strpos($robotsHeaders[0], '200'),
    'value' => $robotsHeaders && strpos($robotsHeaders[0], '200') ? 'Yes' : 'No',
    'note' => 'Visit ' . $robotsUrl . ' to verify'
];

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Deployment Checklist</h1>
    </div>
    
    <div class="alert alert-info">
        <strong>Before going live:</strong> Review each item below and ensure your site is properly configured.
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Check</th>
                            <th style="width: 100px;">Status</th>
                            <th>Current Value</th>
                            <th>Note / Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $check): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($check['name']); ?></td>
                            <td>
                                <?php if ($check['status']): ?>
                                    <span class="badge bg-success">✓ Pass</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Fail</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($check['value']); ?></code></td>
                            <td class="<?php echo $check['status'] ? 'text-muted' : 'text-danger'; ?>">
                                <?php echo htmlspecialchars($check['note']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Post-Deployment Tasks</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check-circle text-success"></i> Submit sitemap to Google Search Console</li>
                        <li><i class="fas fa-check-circle text-success"></i> Configure cron job for GitHub sync</li>
                        <li><i class="fas fa-check-circle text-success"></i> Set up regular database backups</li>
                        <li><i class="fas fa-check-circle text-success"></i> Configure email settings (if using contact form)</li>
                        <li><i class="fas fa-check-circle text-success"></i> Enable Cloudflare or similar CDN</li>
                        <li><i class="fas fa-check-circle text-success"></i> Set up analytics (Google Analytics, etc.)</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="cache-clear.php" class="btn btn-outline-secondary mb-2 d-block"><i class="fas fa-trash"></i> Clear All Caches</a>
                    <a href="sync-manual.php" class="btn btn-outline-info mb-2 d-block"><i class="fab fa-github"></i> Sync GitHub Projects</a>
                    <a href="backup.php" class="btn btn-outline-success mb-2 d-block"><i class="fas fa-database"></i> Create Database Backup</a>
                    <a href="seo.php" class="btn btn-outline-primary mb-2 d-block"><i class="fas fa-search"></i> Configure SEO Settings</a>
                    <a href="settings.php" class="btn btn-outline-warning mb-2 d-block"><i class="fas fa-cog"></i> Site Settings</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Cron Job Setup Instructions</h5>
        </div>
        <div class="card-body">
            <p>For automatic GitHub sync, add this cron job:</p>
            <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px;">
0 */6 * * * /usr/bin/php <?php echo BASE_PATH; ?>/cron/github-sync.php >> <?php echo LOGS_PATH; ?>/sync.log 2>&1
            </pre>
            <p>For cPanel users: Go to "Cron Jobs" → Add the command above.</p>
        </div>
    </div>
</div>

<?php require_once '../templates/admin-footer.php'; ?>
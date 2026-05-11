<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

$db = Database::getInstance();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo'])) {
    $seoSettings = [
        'google_analytics_id' => $_POST['google_analytics_id'] ?? '',
        'google_verification' => $_POST['google_verification'] ?? '',
        'twitter_handle' => $_POST['twitter_handle'] ?? '',
        'json_ld_schema' => $_POST['json_ld_schema'] ?? '',
        'custom_header_scripts' => $_POST['custom_header_scripts'] ?? '',
        'robots_txt_content' => $_POST['robots_txt_content'] ?? "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\nDisallow: /includes/\nSitemap: " . SITE_URL . "/sitemap.xml"
    ];
    
    try {
        foreach ($seoSettings as $key => $value) {
            $db->query(
                "INSERT INTO seo_settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }
        $success = 'SEO settings saved successfully!';
        
        // Clear cache
        getSEOSettings(null, true);
    } catch (Exception $e) {
        $error = 'Failed to save SEO settings: ' . $e->getMessage();
    }
}

// Handle sitemap generation
if (isset($_GET['generate_sitemap'])) {
    $sitemapUrl = SITE_URL . '/sitemap.php';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sitemapUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $success = 'Sitemap generated successfully!';
    } else {
        $error = 'Failed to generate sitemap. Please visit ' . $sitemapUrl . ' manually.';
    }
}

// Get current SEO settings
$currentSettings = getSEOSettings();

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">SEO Settings</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" id="successAlert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="save_seo" value="1">
        
        <ul class="nav nav-tabs mb-3" id="seoTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#global" role="tab">Global SEO</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#robots" role="tab">Robots.txt</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#schema" role="tab">JSON-LD Schema</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#scripts" role="tab">Custom Scripts</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#sitemap" role="tab">Sitemap</button>
            </li>
        </ul>
        
        <div class="tab-content">
            
            <!-- Tab 1: Global SEO -->
            <div class="tab-pane fade show active" id="global" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Global SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Google Analytics ID</label>
                            <input type="text" name="google_analytics_id" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['google_analytics_id'] ?? ''); ?>"
                                   placeholder="G-XXXXXXXXXX or UA-XXXXX-X">
                            <small class="text-muted">Enter your Google Analytics 4 measurement ID.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Google Search Console Verification</label>
                            <input type="text" name="google_verification" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['google_verification'] ?? ''); ?>"
                                   placeholder="google-site-verification=xxxxx">
                            <small class="text-muted">Paste the meta tag content value from Google Search Console.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Twitter Handle</label>
                            <input type="text" name="twitter_handle" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['twitter_handle'] ?? ''); ?>"
                                   placeholder="@username">
                            <small class="text-muted">Your Twitter username for Twitter Cards.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 2: Robots.txt -->
            <div class="tab-pane fade" id="robots" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Robots.txt Editor</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Robots.txt Content</label>
                            <textarea name="robots_txt_content" class="form-control" rows="15" style="font-family: monospace;"><?php 
                                echo htmlspecialchars($currentSettings['robots_txt_content'] ?? "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\nDisallow: /includes/\nDisallow: /cron/\nDisallow: /logs/\nDisallow: /backups/\nSitemap: " . SITE_URL . "/sitemap.xml"); 
                            ?></textarea>
                            <small class="text-muted">This content will be served at: <a href="<?php echo SITE_URL; ?>/robots.txt" target="_blank"><?php echo SITE_URL; ?>/robots.txt</a></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 3: JSON-LD Schema -->
            <div class="tab-pane fade" id="schema" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">JSON-LD Structured Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">JSON-LD Schema</label>
                            <textarea name="json_ld_schema" class="form-control" rows="15" style="font-family: monospace;"><?php 
                                echo htmlspecialchars($currentSettings['json_ld_schema'] ?? '{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "' . (getSettings('site_name') ?? 'My Portfolio') . '",
  "url": "' . SITE_URL . '",
  "sameAs": [
    "https://twitter.com/yourusername",
    "https://github.com/yourusername",
    "https://linkedin.com/in/yourusername"
  ]
}'); 
                            ?></textarea>
                            <small class="text-muted">JSON-LD structured data for rich snippets in search results.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 4: Custom Scripts -->
            <div class="tab-pane fade" id="scripts" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Custom Header Scripts</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Custom Header Scripts</label>
                            <textarea name="custom_header_scripts" class="form-control" rows="10" style="font-family: monospace;"><?php 
                                echo htmlspecialchars($currentSettings['custom_header_scripts'] ?? ''); 
                            ?></textarea>
                            <small class="text-muted">Add custom CSS, JavaScript, or meta tags to the &lt;head&gt; section. (e.g., Facebook Pixel, Google Tag Manager)</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 5: Sitemap -->
            <div class="tab-pane fade" id="sitemap" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Sitemap Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sitemap URL</label>
                            <p><a href="<?php echo SITE_URL; ?>/sitemap.xml" target="_blank" class="btn btn-outline-primary">View Sitemap →</a></p>
                            <p class="text-muted">Your sitemap is automatically generated and cached for 24 hours.</p>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>How to submit to Google Search Console:</strong>
                            <ol class="mt-2 mb-0">
                                <li>Go to <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                                <li>Add your property: <?php echo SITE_URL; ?></li>
                                <li>Go to "Sitemaps" section</li>
                                <li>Enter: <code>sitemap.xml</code></li>
                                <li>Click Submit</li>
                            </ol>
                        </div>
                        
                        <div class="mt-3">
                            <a href="?generate_sitemap=1" class="btn btn-warning" onclick="return confirm('Generate sitemap now? This may take a few seconds.')">Generate Sitemap Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3 mb-4">
            <button type="submit" name="save_seo" value="1" class="btn btn-primary btn-lg">Save All SEO Settings</button>
            <a href="index.php" class="btn btn-secondary btn-lg">Back to Dashboard</a>
        </div>
    </form>
</div>

<script>
    // Auto-hide success alert after 3 seconds
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.display = 'none';
        }, 3000);
    }
    
    // Store active tab in localStorage
    const tabs = document.querySelectorAll('#seoTab button');
    const tabContents = document.querySelectorAll('.tab-pane');
    
    const activeTab = localStorage.getItem('activeSeoTab');
    if (activeTab) {
        tabs.forEach(tab => tab.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('show', 'active'));
        
        const targetBtn = document.querySelector(`#seoTab button[data-bs-target="${activeTab}"]`);
        if (targetBtn) {
            targetBtn.classList.add('active');
            const targetContent = document.querySelector(activeTab);
            if (targetContent) {
                targetContent.classList.add('show', 'active');
            }
        }
    }
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            localStorage.setItem('activeSeoTab', target);
        });
    });
</script>

<?php require_once '../templates/admin-footer.php'; ?>
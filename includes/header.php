<?php
if (!defined('BASE_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

// اطمینان از لود شدن توابع
if (!function_exists('getSettings')) {
    require_once BASE_PATH . '/includes/functions.php';
}

// دریافت تنظیمات از دیتابیس
$settings = getSettings();
$site_name = $settings['site_name'] ?? SITE_NAME;
$site_description = $settings['site_description'] ?? 'Developer portfolio and blog';
$site_logo = $settings['site_logo'] ?? '';
$site_favicon = $settings['site_favicon'] ?? '';

// دریافت تنظیمات SEO
$seoSettings = getSEOSettings();
$google_analytics_id = $seoSettings['google_analytics_id'] ?? '';
$google_verification = $seoSettings['google_verification'] ?? '';
$twitter_handle = $seoSettings['twitter_handle'] ?? '';
$json_ld_schema = $seoSettings['json_ld_schema'] ?? '';
$custom_header_scripts = $seoSettings['custom_header_scripts'] ?? '';

// تنظیمات برگزیده تم از کوکی
$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta name="theme-color" content="#0070F3">
    
    <?php if ($google_verification): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($google_verification); ?>">
    <?php endif; ?>
    
    <?php if ($twitter_handle): ?>
    <meta name="twitter:creator" content="<?php echo htmlspecialchars($twitter_handle); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' . htmlspecialchars($site_name) : htmlspecialchars($site_name); ?></title>
    
    <!-- Fonts -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"> -->
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/personal-cms/assets/css/style.css">
    <link rel="stylesheet" href="https://lib.arvancloud.ir/font-awesome/6.3.0/css/all.min.css">
    <!-- Favicon -->
    <?php if ($site_favicon): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/uploads/<?php echo $site_favicon; ?>">
    <?php else: ?>
    <link rel="icon" type="image/svg+xml" href="/personal-cms/assets/images/favicon.svg">
    <?php endif; ?>
    
    <?php if ($json_ld_schema): ?>
    <script type="application/ld+json">
    <?php echo $json_ld_schema; ?>
    </script>
    <?php endif; ?>
    
    <?php if ($google_analytics_id): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($google_analytics_id); ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo htmlspecialchars($google_analytics_id); ?>');
    </script>
    <?php endif; ?>
    
    <?php if ($custom_header_scripts): ?>
    <?php echo $custom_header_scripts; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Scroll to Top Button -->
    <button class="scroll-top" aria-label="Scroll to top">↑</button>
    
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="/personal-cms/index.php" class="logo">
                <?php if ($site_logo): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $site_logo; ?>" alt="<?php echo htmlspecialchars($site_name); ?>" style="height: 32px;">
                <?php else: ?>
                    <?php echo htmlspecialchars($site_name); ?>
                <?php endif; ?>
            </a>
            
            <div class="hamburger" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <div class="nav-links">
                <a href="/personal-cms/index.php">Home</a>
                <a href="/personal-cms/projects.php">Projects</a>
                <a href="/personal-cms/blog.php">Blog</a>
                <a href="/personal-cms/page.php?slug=about">About</a>
                <a href="/personal-cms/page.php?slug=contact">Contact</a>
            </div>
            
            <button class="theme-toggle" aria-label="Toggle theme">🌙</button>
        </div>
    </nav>
    
    <main class="main-content">
<?php
require_once 'config.php';
$page_title = '404 - Page Not Found';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?php echo SITE_NAME; ?></title>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="/personal-cms/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/personal-cms/index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <button class="theme-toggle" aria-label="Toggle theme">🌙</button>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="container">
            <div class="error-404">
                <h1 class="gradient-text">404</h1>
                <h2>Page not found</h2>
                <p style="max-width: 500px; margin: 0 auto 32px;">
                    Oops! The page you're looking for doesn't exist or has been moved.
                </p>
                
                <form method="GET" action="/personal-cms/search.php" style="max-width: 400px; margin: 0 auto 32px;">
                    <div style="display: flex; gap: 12px;">
                        <input type="text" name="q" placeholder="Search the site..." style="flex: 1;">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
                
                <a href="/personal-cms/index.php" class="btn btn-primary">← Back to Home</a>
            </div>
        </div>
    </main>
    
    <script src="/personal-cms/assets/js/theme.js"></script>
</body>
</html>
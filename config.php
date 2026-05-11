<?php
// ==============================================
// FILE: config.php (نسخه پاک شده - بدون توابع تکراری)
// ==============================================

// ==============================================
// ERROR REPORTING
// ==============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/errors.log');

// ==============================================
// TIMEZONE
// ==============================================
date_default_timezone_set('Asia/Tehran');

// ==============================================
// DATABASE CONFIGURATION
// ==============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'portfolio_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ==============================================
// URL CONFIGURATION
// ==============================================
define('SITE_URL', 'http://localhost/personal-cms');
define('SITE_NAME', 'My Portfolio');
define('SITE_DESCRIPTION', 'Developer portfolio and blog');
define('ADMIN_URL', SITE_URL . '/admin');
define('ASSETS_URL', SITE_URL . '/assets');


// ==============================================
// PATH CONFIGURATION
// ==============================================
define('BASE_PATH', __DIR__);
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('CACHE_PATH', BASE_PATH . '/cache');
define('LOGS_PATH', BASE_PATH . '/logs');
define('BACKUP_PATH', BASE_PATH . '/backups');

// ==============================================
// UPLOAD LIMITS
// ==============================================
define('MAX_FILE_SIZE', 2 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ==============================================
// CACHE TTL
// ==============================================
define('GITHUB_CACHE_TTL', 21600);
define('SITEMAP_CACHE_TTL', 86400);

// ==============================================
// SESSION SETTINGS
// ==============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==============================================
// CREATE REQUIRED DIRECTORIES
// ==============================================
$directories = [UPLOAD_PATH, CACHE_PATH, LOGS_PATH, BACKUP_PATH];
$subDirs = ['/posts', '/projects', '/thumbs'];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

foreach ($subDirs as $sub) {
    $subPath = UPLOAD_PATH . $sub;
    if (!file_exists($subPath)) {
        mkdir($subPath, 0755, true);
    }
}

// ==============================================
// ERROR HANDLERS
// ==============================================
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $log = date('Y-m-d H:i:s') . " - Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($log, 3, LOGS_PATH . '/errors.log');
    if (ini_get('display_errors')) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:10px; margin:10px; border-radius:5px;'>Error: $errstr</div>";
    }
    return true;
}
set_error_handler('customErrorHandler');

function customExceptionHandler($exception) {
    $log = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($log, 3, LOGS_PATH . '/errors.log');
    if (ini_get('display_errors')) {
        echo "<div style='background:#f8d7da; color:#721c14; padding:10px; margin:10px; border-radius:5px;'>Exception: " . $exception->getMessage() . "</div>";
    }
}
set_exception_handler('customExceptionHandler');

// ==============================================
// AUTO-LOAD CLASSES
// ==============================================
spl_autoload_register(function ($class) {
    $class_path = BASE_PATH . '/includes/' . $class . '.php';
    if (file_exists($class_path)) {
        require_once $class_path;
    }
});

// ==============================================
//注意: توابع getSettings و getSEOSettings 
// الان در فایل functions.php قرار دارند
// ==============================================
?>
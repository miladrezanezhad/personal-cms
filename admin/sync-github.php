<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';  // این خط را اضافه کنید

Auth::requireLogin();

// Only allow CLI or specific IP for security
if (php_sapi_name() !== 'cli') {
    $allowedIps = ['127.0.0.1', '::1'];
    if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
        die('Access denied');
    }
}

$github = new GitHubAPI();
$result = $github->syncAllProjects();

$logMessage = date('Y-m-d H:i:s') . " - Synced: {$result['success']}, Failed: {$result['failed']}\n";
file_put_contents(LOG_DIR . '/sync.log', $logMessage, FILE_APPEND);

echo "Sync completed: {$result['success']} projects synced, {$result['failed']} failed\n";
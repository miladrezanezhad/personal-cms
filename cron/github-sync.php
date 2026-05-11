<?php
// ==============================================
// FILE: cron/github-sync.php
// ==============================================

require_once dirname(__DIR__) . '/config.php';

// Log function
function logSync($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message\n";
    file_put_contents(LOGS_PATH . '/sync.log', $logMessage, FILE_APPEND);
    
    // Also echo for CLI output
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

logSync('========== SYNC STARTED ==========');

// Check if GitHub token exists
$githubToken = getSettings('github_token');
if (empty($githubToken)) {
    logSync('WARNING: No GitHub token found. Rate limits will be restricted to 60 requests/hour.', 'warning');
}

$github = new GitHubAPI();
$result = $github->syncAllProjects();

logSync("Sync completed: {$result['success']} succeeded, {$result['failed']} failed");

// Send email alert if failures occurred
if ($result['failed'] > 0) {
    $adminEmail = getSettings('contact_email');
    if ($adminEmail) {
        $subject = "GitHub Sync Alert - " . SITE_NAME;
        $message = "GitHub sync completed with failures:\n";
        $message .= "Successful: {$result['success']}\n";
        $message .= "Failed: {$result['failed']}\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $message .= "Check logs at: " . SITE_URL . "/admin/logs-viewer.php";
        
        // Use mail() or log only
        error_log("Sync alert would be sent to: $adminEmail - $subject");
        logSync("Alert would be sent to admin: {$result['failed']} failures", 'alert');
    }
}

logSync('========== SYNC ENDED ==========');
logSync('');
?>
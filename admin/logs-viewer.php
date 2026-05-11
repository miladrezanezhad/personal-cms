<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

$logFiles = [
    'sync.log' => 'GitHub Sync Log',
    'errors.log' => 'PHP Errors Log',
    'app.log' => 'Application Log'
];

$selectedLog = $_GET['log'] ?? 'sync.log';
$filter = $_GET['filter'] ?? '';
$lines = 500; // Number of lines to display

$logContent = '';
$logPath = LOGS_PATH . '/' . $selectedLog;
$message = '';

if (file_exists($logPath)) {
    $content = file_get_contents($logPath);
    $lines_array = explode("\n", $content);
    $lines_array = array_reverse($lines_array);
    $lines_array = array_slice($lines_array, 0, $lines);
    
    if ($filter) {
        $lines_array = array_filter($lines_array, function($line) use ($filter) {
            return stripos($line, $filter) !== false;
        });
    }
    
    $logContent = implode("\n", $lines_array);
}

// Handle clear log
if (isset($_POST['clear_log'])) {
    if (file_exists($logPath)) {
        file_put_contents($logPath, '');
        $message = "Log cleared: " . htmlspecialchars($selectedLog);
    }
}

// Handle download log
if (isset($_GET['download'])) {
    if (file_exists($logPath)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $selectedLog . '"');
        readfile($logPath);
        exit;
    }
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Logs Viewer</h1>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success" id="successAlert"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" class="row g-3">
                <div class="col-auto">
                    <select name="log" class="form-select">
                        <?php foreach ($logFiles as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selectedLog === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="filter" class="form-control" placeholder="Filter..." value="<?php echo htmlspecialchars($filter); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="logs-viewer.php?log=<?php echo urlencode($selectedLog); ?>" class="btn btn-secondary">Clear Filter</a>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <form method="POST" style="display: inline-block;">
                <button type="submit" name="clear_log" class="btn btn-danger" onclick="return confirm('Clear this log file?')">Clear Log</button>
            </form>
            <a href="logs-viewer.php?log=<?php echo urlencode($selectedLog); ?>&download=1" class="btn btn-success ms-2">Download</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php echo $logFiles[$selectedLog] ?? $selectedLog; ?> 
                <small class="text-muted">(Last <?php echo $lines; ?> lines)</small>
            </h5>
        </div>
        <div class="card-body p-0">
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 15px; margin: 0; overflow-x: auto; font-size: 12px; font-family: 'Courier New', monospace; max-height: 600px;"><?php 
                echo htmlspecialchars($logContent ?: 'No logs found.'); 
            ?></pre>
        </div>
    </div>
    
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-folder-open"></i> Log location: <code>/logs/<?php echo $selectedLog; ?></code>
        </small>
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
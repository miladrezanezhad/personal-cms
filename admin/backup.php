<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

$db = Database::getInstance();
$error = '';
$success = '';
$debug = '';

// اطمینان از وجود پوشه backups
if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0755, true);
    $debug .= "Created backups folder<br>";
}

// =============================================
// تابع ساده بکاپ
// =============================================
function simpleBackup() {
    global $debug;
    
    $pdo = Database::getInstance()->getConnection();
    $filename = 'backup_' . date('Ymd_His') . '.sql';
    $filepath = BACKUP_PATH . '/' . $filename;
    
    $debug .= "Backup path: $filepath<br>";
    
    // دریافت لیست جداول
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $debug .= "Found tables: " . implode(', ', $tables) . "<br>";
    
    if (empty($tables)) {
        throw new Exception("No tables found");
    }
    
    $output = "-- Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: " . DB_NAME . "\n\n";
    
    foreach ($tables as $table) {
        $debug .= "Processing table: $table<br>";
        
        // ساختار جدول
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";
        
        // داده‌های جدول
        $data = $pdo->query("SELECT * FROM `$table`");
        $rows = $data->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));
                $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    $bytes = file_put_contents($filepath, $output);
    $debug .= "Written $bytes bytes to file<br>";
    
    if ($bytes === false || $bytes == 0) {
        throw new Exception("Failed to write file");
    }
    
    return $filepath;
}

// =============================================
// Handle Actions
// =============================================

// ایجاد بکاپ جدید - با دیباگ
if (isset($_POST['create_backup'])) {
    $debug .= "Create backup button clicked<br>";
    try {
        $backupFile = simpleBackup();
        if (file_exists($backupFile) && filesize($backupFile) > 0) {
            $success = "Backup created: " . basename($backupFile) . " (" . number_format(filesize($backupFile) / 1024, 2) . " KB)";
            
            // حذف بکاپ‌های قدیمی
            $backups = glob(BACKUP_PATH . '/backup_*.sql');
            if (is_array($backups) && count($backups) > 10) {
                usort($backups, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                foreach (array_slice($backups, 10) as $old) {
                    @unlink($old);
                }
            }
        } else {
            $error = "Backup file not created or empty";
        }
    } catch (Exception $e) {
        $error = "Backup error: " . $e->getMessage();
    }
}

// حذف بکاپ
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filepath = BACKUP_PATH . '/' . $file;
    if (file_exists($filepath)) {
        @unlink($filepath);
        $success = "Deleted: " . htmlspecialchars($file);
        header("Location: backup.php");
        exit;
    }
}

// دانلود بکاپ
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = BACKUP_PATH . '/' . $file;
    if (file_exists($filepath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// دریافت لیست بکاپ‌ها
$backups = glob(BACKUP_PATH . '/backup_*.sql');
if (!is_array($backups)) {
    $backups = [];
}
rsort($backups);

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Database Backup & Restore</h1>
    </div>
    
    <?php if ($debug && isset($_POST['create_backup'])): ?>
        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            <?php echo $debug; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" id="successAlert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Create Backup Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-database"></i> Create Backup</h5>
                </div>
                <div class="card-body">
                    <p>Create a full database backup including all tables.</p>
                    <form method="POST" id="backupForm">
                        <button type="submit" name="create_backup" id="createBackupBtn" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-download"></i> Create Backup Now
                        </button>
                    </form>
                    <hr>
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle"></i> Backup location: <code><?php echo BACKUP_PATH; ?></code><br>
                            <i class="fas fa-clock"></i> Only the last 10 backups are kept.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Import Backup Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Import / Restore Backup</h5>
                </div>
                <div class="card-body">
                    <p>Restore your database from a backup file (.sql).</p>
                    
                    <h6>Upload a backup file:</h6>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="import_file" class="form-control" accept=".sql" required>
                        </div>
                        <button type="submit" name="import_backup" class="btn btn-success w-100" onclick="return confirm('WARNING: This will overwrite your current database! Are you sure?')">
                            <i class="fas fa-upload"></i> Upload & Import
                        </button>
                    </form>
                    
                    <hr>
                    
                    <?php if (!empty($backups)): ?>
                    <h6>Or import from existing backup:</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <select name="existing_file" class="form-select" required>
                                <option value="">Select a backup file...</option>
                                <?php foreach ($backups as $backup): ?>
                                <option value="<?php echo basename($backup); ?>">
                                    <?php echo basename($backup); ?> (<?php echo number_format(filesize($backup) / 1024, 2); ?> KB)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="import_existing" class="btn btn-warning w-100" onclick="return confirm('WARNING: This will overwrite your current database! Are you sure?')">
                            <i class="fas fa-exchange-alt"></i> Import Selected
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Backups List -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Existing Backups</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($backups)): ?>
                <div class="alert alert-info m-3">No backups found. Click "Create Backup Now" to create your first backup.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <?php 
                            $filename = basename($backup);
                            $filesize = filesize($backup);
                            $sizeUnit = $filesize > 1024 * 1024 ? number_format($filesize / (1024 * 1024), 2) . ' MB' : number_format($filesize / 1024, 2) . ' KB';
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($filename); ?></code></td>
                                <td><?php echo $sizeUnit; ?></td>
                                <td><?php echo date('Y-m-d H:i:s', filemtime($backup)); ?></td>
                                <td>
                                    <a href="?download=<?php echo urlencode($filename); ?>" class="btn btn-sm btn-primary">Download</a>
                                    <a href="?delete=<?php echo urlencode($filename); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this backup?')">Delete</a>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // loading state for backup button
    const backupForm = document.getElementById('backupForm');
    const backupBtn = document.getElementById('createBackupBtn');
    
    if (backupForm && backupBtn) {
        backupForm.addEventListener('submit', function() {
            backupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating backup, please wait...';
            backupBtn.disabled = true;
        });
    }
    
    // auto hide success message
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.display = 'none';
        }, 5000);
    }
</script>

<?php require_once '../templates/admin-footer.php'; ?>
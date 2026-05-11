<?php
require_once 'config.php';

echo "Backup Path: " . BACKUP_PATH . "<br>";
echo "Is writable: " . (is_writable(BACKUP_PATH) ? 'Yes' : 'No') . "<br>";

// تست نوشتن فایل
$testFile = BACKUP_PATH . '/test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "Write test: SUCCESS<br>";
    unlink($testFile);
} else {
    echo "Write test: FAILED<br>";
}

// تست اتصال دیتابیس
$db = Database::getInstance();
$tables = $db->fetchAll("SHOW TABLES");
echo "Number of tables: " . count($tables) . "<br>";
?>
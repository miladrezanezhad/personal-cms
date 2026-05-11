<?php
// ==============================================
// FILE: includes/functions.php
// ==============================================

// توابع پایه
function slugify($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function truncate($text, $limit, $ending = '...') {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . $ending;
}

function formatDate($date, $format = 'Y/m/d') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function isActive($path) {
    $current = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($current, $path) !== false ? 'active' : '';
}

// تابع دریافت تنظیمات سایت با قابلیت refresh (بروزرسانی کش)
function getSettings($key = null, $refresh = false) {
    static $settings = null;
    
    // اگر refresh=true باشد، کش را پاک کن
    if ($refresh) {
        $settings = null;
    }
    
    try {
        $db = Database::getInstance();
    } catch (Exception $e) {
        return $key === null ? [] : null;
    }
    
    if ($settings === null) {
        try {
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    
    if ($key === null) {
        return $settings;
    }
    
    return isset($settings[$key]) ? $settings[$key] : null;
}

// تابع دریافت تنظیمات سئو با قابلیت refresh
function getSEOSettings($key = null, $refresh = false) {
    static $seoSettings = null;
    
    if ($refresh) {
        $seoSettings = null;
    }
    
    try {
        $db = Database::getInstance();
    } catch (Exception $e) {
        return $key === null ? [] : null;
    }
    
    if ($seoSettings === null) {
        try {
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM seo_settings");
            $seoSettings = [];
            foreach ($rows as $row) {
                $seoSettings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $seoSettings = [];
        }
    }
    
    if ($key === null) {
        return $seoSettings;
    }
    
    return isset($seoSettings[$key]) ? $seoSettings[$key] : null;
}

// تابع نمایش وضعیت (برای admin)
function getStatusBadge($status) {
    if ($status === 'published') {
        return '<span class="badge bg-success">Published</span>';
    } elseif ($status === 'draft') {
        return '<span class="badge bg-secondary">Draft</span>';
    }
    return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}

// تابع نمایش وضعیت همگامسازی گیت‌هاب
function getSyncStatusBadge($status) {
    switch ($status) {
        case 'synced':
            return '<span class="badge bg-success">Synced</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'failed':
            return '<span class="badge bg-danger">Failed</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

// تابع تولید slug یکتا
function generateSlug($string, $table, $id = null) {
    $db = Database::getInstance();
    $slug = slugify($string);
    $original = $slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT id FROM $table WHERE slug = ?";
        $params = [$slug];
        if ($id) {
            $sql .= " AND id != ?";
            $params[] = $id;
        }
        $exists = $db->fetchOne($sql, $params);
        
        if (!$exists) {
            break;
        }
        
        $slug = $original . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// تابع دریافت دسته‌بندی‌ها
function getPostCategories() {
    $db = Database::getInstance();
    return $db->fetchAll("SELECT * FROM categories ORDER BY name");
}

// تابع لاگ کردن
function logMessage($message, $file = 'app.log') {
    if (!defined('LOGS_PATH')) return;
    $logPath = LOGS_PATH . '/' . $file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logPath, "[$timestamp] $message\n", FILE_APPEND);
}

// تابع پاکسازی ورودی
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// تابع دریافت تنظیمات هدر/فوتر از دیتابیس با مقدار پیش‌فرض
function getSetting($key, $default = '') {
    $value = getSettings($key);
    return $value !== null && $value !== '' ? $value : $default;
}
?>
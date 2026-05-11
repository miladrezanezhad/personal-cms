<?php
// ==============================================
// FILE: includes/upload.php
// ==============================================

class Upload {
    private static $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private static $maxSize = 2097152; // 2MB
    private static $thumbSize = 150;
    
    public static function uploadImage($file, $folder, $createThumb = true) {
        // استفاده از ثابت UPLOAD_PATH که در config.php تعریف شده
        if (!defined('UPLOAD_PATH')) {
            return ['success' => false, 'error' => 'UPLOAD_PATH constant not defined'];
        }
        
        // Validate file
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $errorMsg = $errors[$file['error']] ?? 'Unknown upload error';
            return ['success' => false, 'error' => $errorMsg];
        }
        
        if (!in_array($file['type'], self::$allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'];
        }
        
        if ($file['size'] > self::$maxSize) {
            return ['success' => false, 'error' => 'File too large. Max 2MB'];
        }
        
        // Create folder if not exists
        $uploadPath = UPLOAD_PATH . '/' . $folder;
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        // Create thumbs folder if needed
        if ($createThumb) {
            $thumbPath = UPLOAD_PATH . '/thumbs';
            if (!file_exists($thumbPath)) {
                mkdir($thumbPath, 0777, true);
            }
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $extension;
        
        $fullPath = $uploadPath . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
        }
        
        // Create thumbnail
        $thumbRelativePath = null;
        if ($createThumb) {
            $thumb = self::createThumbnail($fullPath, $extension);
            if ($thumb) {
                $thumbFullPath = UPLOAD_PATH . '/thumbs/' . $filename;
                imagejpeg($thumb, $thumbFullPath, 80);
                imagedestroy($thumb);
                $thumbRelativePath = 'thumbs/' . $filename;
            }
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $folder . '/' . $filename,
            'thumb' => $thumbRelativePath
        ];
    }
    
    private static function createThumbnail($imagePath, $extension) {
        list($width, $height) = getimagesize($imagePath);
        
        // Calculate crop dimensions
        $size = min($width, $height);
        $x = ($width - $size) / 2;
        $y = ($height - $size) / 2;
        
        // Create source image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $source = imagecreatefrompng($imagePath);
                break;
            case 'gif':
                $source = imagecreatefromgif($imagePath);
                break;
            case 'webp':
                $source = imagecreatefromwebp($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        $thumb = imagecreatetruecolor(self::$thumbSize, self::$thumbSize);
        
        // Handle transparency for PNG
        if ($extension === 'png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);
        }
        
        imagecopyresampled($thumb, $source, 0, 0, $x, $y, self::$thumbSize, self::$thumbSize, $size, $size);
        
        imagedestroy($source);
        return $thumb;
    }
    
    public static function deleteImage($path) {
        if (!defined('UPLOAD_PATH')) {
            return false;
        }
        
        $fullPath = UPLOAD_PATH . '/' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Delete thumbnail if exists
        $filename = basename($path);
        $thumbPath = UPLOAD_PATH . '/thumbs/' . $filename;
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        
        return true;
    }
}
?>
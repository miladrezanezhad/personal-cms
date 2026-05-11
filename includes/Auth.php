<?php
// ==============================================
// FILE: includes/Auth.php
// ==============================================

class Auth {
    private static $db;
    
    private static function getDb() {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    public static function login($username, $password) {
        $db = self::getDb();
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }
    
    public static function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;
        $db = self::getDb();
        return $db->fetchOne("SELECT id, username, email, role FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . ADMIN_URL . '/login.php');
            exit;
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('HTTP/1.0 403 Forbidden');
            die('Access denied. Admin privileges required.');
        }
    }
}
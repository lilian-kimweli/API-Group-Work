<?php
class Auth {
    
    // Check if user is logged in
    public static function check() {
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }
    
    // Check if user has specific role
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    // Check if user has any of the specified roles
    public static function hasAnyRole($roles) {
        if (!isset($_SESSION['role'])) return false;
        return in_array($_SESSION['role'], $roles);
    }
    
    // Redirect to login if not authenticated
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }
    
    // Redirect if user doesn't have required role
    public static function requireRole($role) {
        self::requireAuth();
        if (!self::hasRole($role)) {
            header('Location: access_denied.php');
            exit;
        }
    }
    
    // Redirect if user doesn't have any of the required roles
    public static function requireAnyRole($roles) {
        self::requireAuth();
        if (!self::hasAnyRole($roles)) {
            header('Location: access_denied.php');
            exit;
        }
    }
    
    // Get current user's role
    public static function getRole() {
        return $_SESSION['role'] ?? 'guest';
    }
    
    // Check if user is admin
    public static function isAdmin() {
        return self::hasRole('admin');
    }
    
    // Check if user is manager
    public static function isManager() {
        return self::hasRole('manager');
    }
    
    // Check if user is customer
    public static function isCustomer() {
        return self::hasRole('customer');
    }
}
?>
<?php
/**
 * Session Management
 * Handles secure session configuration and user authentication checks
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
    
    session_start();
}

// Session timeout: 30 minutes
define('SESSION_TIMEOUT', 1800);

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (!isset($_SESSION['userid']) || !isset($_SESSION['lastactivity'])) {
        return false;
    }
    if (time() - $_SESSION['lastactivity'] > SESSION_TIMEOUT) {
        logout();
        return false;
    }
    $_SESSION['lastactivity'] = time();
    return true;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = array();
    session_destroy();
    header('Location: ../pages/login.php');
    exit();
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php');
        exit();
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /pages/dashboard.php');
        exit();
    }
}
?>

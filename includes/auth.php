<?php
// ============================================================
// includes/auth.php
// Handles login, logout, audit logging, login attempt logging
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';

/**
 * Attempt to log in a user.
 * Returns true on success, or an error string on failure.
 */
function loginUser(string $email, string $password): bool|string {
    global $pdo;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Validate email domain
    if (!str_ends_with(strtolower(trim($email)), '@moh.gov.my')) {
        logLoginAttempt($email, false, $ip);
        return 'Only @moh.gov.my email addresses are allowed.';
    }

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT userid, email, passwordhash, role, isactive FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    // User not found
    if (!$user) {
        logLoginAttempt($email, false, $ip);
        return 'Invalid email or password.';
    }

    // Account disabled
    if ((int)$user['isactive'] === 0) {
        logLoginAttempt($email, false, $ip);
        return 'Your account has been disabled. Contact the administrator.';
    }

    // Wrong password
    if (!password_verify($password, $user['passwordhash'])) {
        logLoginAttempt($email, false, $ip);
        return 'Invalid email or password.';
    }

    // SUCCESS — set up session
    session_regenerate_id(true);
    $_SESSION['userid']       = $user['userid'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['lastactivity'] = time();

    // Update last login timestamp
    $upd = $pdo->prepare("UPDATE users SET lastlogin = CURRENT_TIMESTAMP WHERE userid = ?");
    $upd->execute([$user['userid']]);

    // Log to auditlog
    logAuditEvent($user['userid'], 'login', null, 'User logged in', $ip);

    // Log to loginattempts
    logLoginAttempt($email, true, $ip);

    return true;
}

/**
 * Log out the current user.
 */
function logoutUser(): void {
    global $pdo;

    if (isLoggedIn()) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        logAuditEvent($_SESSION['userid'], 'logout', null, 'User logged out', $ip);
    }

    $_SESSION = [];
    session_destroy();
    header('Location: /pages/login.php');
    exit;
}

/**
 * Insert a row into auditlog.
 */
function logAuditEvent(int $userid, string $action, ?int $recordid, string $details, string $ip): void {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO auditlog (userid, action, recordid, details, ipaddress)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userid, $action, $recordid, $details, $ip]);
}

/**
 * Insert a row into loginattempts.
 */
function logLoginAttempt(string $email, bool $success, string $ip): void {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO loginattempts (email, success, ipaddress)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $success ? 1 : 0, $ip]);
}
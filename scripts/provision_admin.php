<?php
// ============================================================
// provision_admin.php
// One-time script to create the first admin user.
// Run from terminal: php scripts/provision_admin.php
// NEVER place this file inside public/
// ============================================================

require_once __DIR__ . '/../config/database.php';

// ============================================================
// EDIT THESE VALUES BEFORE RUNNING
// ============================================================
$adminEmail    = 'admin@moh.gov.my';   // Must end with @moh.gov.my
$adminPassword = 'admin_password';          // Min 8 characters, change this!
$adminRole     = 'admin';
// ============================================================

echo "\n=== MOH Sample Tracking System - Admin Provisioning ===\n\n";

// Validate email domain
if (!str_ends_with($adminEmail, '@moh.gov.my')) {
    die("ERROR: Email must end with @moh.gov.my\n");
}

// Validate password length (system requires exactly 8 characters)
if (strlen($adminPassword) < 8) {
    die("ERROR: Password must be at least 8 characters\n");
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->execute([$adminEmail]);
if ($stmt->fetchColumn() > 0) {
    die("ERROR: Email {$adminEmail} already exists in the database.\n");
}

// Hash password using Argon2ID
$passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64MB
    'time_cost'   => 4,
    'threads'     => 1,
]);

if (!$passwordHash) {
    die("ERROR: Failed to hash password. Check PHP Argon2ID support.\n");
}

// Insert admin user
$stmt = $pdo->prepare("
    INSERT INTO users (email, passwordhash, role, isactive)
    VALUES (?, ?, ?, 1)
");
$stmt->execute([$adminEmail, $passwordHash, $adminRole]);

$newUserId = $pdo->lastInsertId();

// Confirm
echo "SUCCESS: Admin user created!\n";
echo "  User ID  : {$newUserId}\n";
echo "  Email    : {$adminEmail}\n";
echo "  Role     : {$adminRole}\n";
echo "  Hash algo: Argon2ID\n";
echo "  Active   : Yes\n\n";
echo "You can now log in at: http://localhost/pages/login.php\n";
echo "Remember to delete or restrict this script after use.\n\n";
<?php
ob_start(); // Buffer all output to prevent header issues
// ============================================================
// pages/login.php
// Login form — accessible without authentication
// ============================================================

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';

// Already logged in — redirect to dashboard
if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = loginUser($email, $password);
        if ($result === true) {
            header('Location: /pages/dashboard.php');
            exit;
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MOH Sample Tracking</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-box {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-box h1 {
            font-size: 1.4rem;
            margin-bottom: 0.3rem;
            color: #1a3c5e;
        }
        .login-box p.subtitle {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
            color: #333;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        input:focus { outline: none; border-color: #1a3c5e; }
        button[type="submit"] {
            width: 100%;
            padding: 0.7rem;
            background: #1a3c5e;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }
        button[type="submit"]:hover { background: #15314e; }
        .error {
            background: #fde8e8;
            color: #b91c1c;
            border: 1px solid #f5c6c6;
            border-radius: 4px;
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .footer-note {
            font-size: 0.78rem;
            color: #999;
            text-align: center;
            margin-top: 1.2rem;
        }
    </style>
</head>
<body>
<div class="login-box">
    <h1>MOH Laboratory</h1>
    <p class="subtitle">Sample Tracking System — Staff Login</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="POST" action="/pages/login.php">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="you@moh.gov.my"
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
            required
            autocomplete="email"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
        >

        <button type="submit">Log In</button>
    </form>

    <p class="footer-note">Access restricted to @moh.gov.my accounts only.</p>
</div>
</body>
</html>
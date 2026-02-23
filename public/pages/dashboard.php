<?php
ob_start();

require_once __DIR__ . '/../../includes/session.php';  // session_start() happens here
require_once __DIR__ . '/../../includes/auth.php';


requireLogin(); // Redirect to login.php if not authenticated

$userEmail = htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES);
$userRole  = htmlspecialchars($_SESSION['role']  ?? '', ENT_QUOTES);
$isAdmin   = isAdmin();

// Handle logout button
if (isset($_POST['logout'])) {
    logoutUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — MOH Sample Tracking</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; color: #333; }
        header {
            background: #1a3c5e;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { font-size: 1.2rem; }
        header .user-info { font-size: 0.85rem; text-align: right; }
        header .user-info span { display: block; }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: bold;
            background: <?= $isAdmin ? '#f59e0b' : '#3b82f6' ?>;
            color: white;
            margin-top: 0.2rem;
        }
        main { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        h2 { font-size: 1.3rem; color: #1a3c5e; margin-bottom: 1rem; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem 1rem;
            text-align: center;
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            text-decoration: none;
            color: #1a3c5e;
            font-weight: bold;
            font-size: 0.95rem;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        .card:hover { border-color: #1a3c5e; }
        .card .icon { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
        .card.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
        .section-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #999;
            margin-bottom: 0.5rem;
        }
        form.logout { display: inline; }
        button.logout-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.6);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        button.logout-btn:hover { background: rgba(255,255,255,0.15); }
    </style>
</head>
<body>

<header>
    <h1>MOH Sample Tracking System</h1>
    <div class="user-info">
        <span><?= $userEmail ?></span>
        <span class="badge"><?= strtoupper($userRole) ?></span>
        <form method="POST" action="/pages/dashboard.php" class="logout" style="margin-top:0.4rem">
            <button type="submit" name="logout" class="logout-btn">Log Out</button>
        </form>
    </div>
</header>

<main>
    <h2>Welcome back, <?= $userEmail ?>!</h2>

    <p class="section-label">Sample Management</p>
    <div class="grid">
        <a href="/pages/add-sample.php" class="card disabled">
            <span class="icon">➕</span>Add Sample
        </a>
        <a href="/pages/search-samples.php" class="card disabled">
            <span class="icon">🔍</span>Search Samples
        </a>
        <a href="/pages/my-records.php" class="card disabled">
            <span class="icon">📋</span>My Records
        </a>
    </div>

    <?php if ($isAdmin): ?>
    <p class="section-label">Admin Panel</p>
    <div class="grid">
        <a href="/pages/manage-users.php" class="card disabled">
            <span class="icon">👥</span>Manage Users
        </a>
        <a href="/pages/audit-log.php" class="card disabled">
            <span class="icon">📜</span>Audit Log
        </a>
        <a href="/pages/system-stats.php" class="card disabled">
            <span class="icon">📊</span>System Stats
        </a>
    </div>
    <?php endif; ?>

    <p style="font-size:0.8rem;color:#aaa;margin-top:1rem;">
        Pages marked as unavailable are coming in the next development phase.
    </p>
</main>

</body>
</html>
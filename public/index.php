<!DOCTYPE html>
<html>
<head>
    <title>MOH Lab Sample Tracking System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f4f4f4; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; font-weight: bold; font-size: 18px; }
        .info { background: #ecf0f1; padding: 15px; margin: 10px 0; border-left: 4px solid #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 MOH Laboratory Sample Tracking System</h1>
        <p class="success">✓ Apache Web Server is running!</p>
        <p class="success">✓ PHP is working!</p>
        
        <div class="info">
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
            <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <h3>✅ System Status</h3>
        <ul>
            <li>Apache HTTP Server: Running</li>
            <li>PHP: Active (Version <?php echo PHP_VERSION; ?>)</li>
            <li>Project Directory: Configured</li>
        </ul>
        
        <h3>📋 Next Steps:</h3>
        <ol>
            <li>Install MySQL/MariaDB database</li>
            <li>Create database and tables</li>
            <li>Configure database connection</li>
            <li>Build login system</li>
            <li>Develop sample management features</li>
        </ol>
    </div>
</body>
</html>

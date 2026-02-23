<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php'; // provides db() function
$pdo = db(); // get PDO instance
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Test</title>
    <style>
        body { font-family: Arial; margin: 50px; background: #f4f4f4; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Connection Test</h1>
        
        <?php
        try {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll();
            
            echo "<p class='success'>✓ Database connected successfully!</p>";
            echo "<h3>Tables found:</h3><ul>";
            foreach ($tables as $table) {
                echo "<li>" . array_values($table)[0] . "</li>";
            }
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        }
        ?>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>

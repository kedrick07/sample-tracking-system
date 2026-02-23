<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdoInstance = null;

    if ($pdoInstance instanceof PDO) {
        return $pdoInstance;
    }

    require __DIR__ . '/../config/database.php'; // must create $pdo

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('config/database.php did not create $pdo.');
    }

    $pdoInstance = $pdo;
    return $pdoInstance;
}

<?php
declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = __DIR__ . '/../storage/data/app.db';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

<?php
declare(strict_types=1);

/**
 * Bir kerelik kurulum scripti:
 *   php includes/init_db.php
 */

require __DIR__ . '/db.php';

$dbPath = __DIR__ . '/../storage/data/app.db';
if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = get_db();

$pdo->exec('
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        display_name TEXT NOT NULL
    )
');

$pdo->exec('
    CREATE TABLE photos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        title TEXT NOT NULL,
        is_private INTEGER NOT NULL DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
');

$users = [
    ['azad',   'melekazad',      'Azad Çetin'],
    ['yagiz',  '58sevdalisi',    'Yağız Abuhan'],
    ['admin',  'YonetimPaneli!', 'Sistem Yöneticisi'],
];

$userIds = [];
$insertUser = $pdo->prepare('INSERT INTO users (username, password_hash, display_name) VALUES (?, ?, ?)');
foreach ($users as [$uname, $pass, $display]) {
    $insertUser->execute([$uname, password_hash($pass, PASSWORD_DEFAULT), $display]);
    $userIds[$uname] = (int) $pdo->lastInsertId();
}

$insertPhoto = $pdo->prepare('INSERT INTO photos (user_id, filename, title, is_private) VALUES (?, ?, ?, ?)');

// id 1-2: Azad
$insertPhoto->execute([$userIds['azad'], 'beach.svg', "Azad'ın sülalesinden kalma sahil", 1]);
$insertPhoto->execute([$userIds['azad'], 'mountain.svg', 'Gabar Dağı - favorite spot', 1]);

// id 3-4: Yağız
$insertPhoto->execute([$userIds['yagiz'], 'city.svg', 'İstanbul - city view', 1]);
$insertPhoto->execute([$userIds['yagiz'], 'cat.svg', 'Kedim', 1]);

// id 5: Admin - sıradan görünen
$insertPhoto->execute([$userIds['admin'], 'office.svg', 'Aylık Yönetim Raporu', 1]);

// id 6: Admin - GERÇEK GİZLİ hedef
$insertPhoto->execute([$userIds['admin'], 'trophy.svg', 'Yılın En Gizli Ödülü (TOP SECRET)', 1]);

echo "Veritabanı oluşturuldu: {$dbPath}\n";
echo "Kullanıcılar:\n";
foreach ($users as [$uname, $pass, $display]) {
    echo "  - {$uname} / {$pass}  ({$display})\n";
}

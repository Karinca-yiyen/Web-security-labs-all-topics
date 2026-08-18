<?php
declare(strict_types=1);

/**
 * Bu script sadece bir defalık kurulum içindir:
 *   php includes/init_db.php
 * Veritabanını sıfırdan oluşturur ve örnek kullanıcı/fotoğraf verisiyle doldurur.
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
    ['azad',   'melekazad',  'Azad Çetin'],
    ['yagız', '58sevdalısı',   'Yagız abuhan'],
    ['admin',  'YonetimPaneli!','Sistem Yöneticisi'],
];

$userIds = [];
$insertUser = $pdo->prepare('INSERT INTO users (username, password_hash, display_name) VALUES (?, ?, ?)');
foreach ($users as [$uname, $pass, $display]) {
    $insertUser->execute([$uname, password_hash($pass, PASSWORD_DEFAULT), $display]);
    $userIds[$uname] = (int) $pdo->lastInsertId();
}

$insertPhoto = $pdo->prepare('INSERT INTO photos (user_id, filename, title, is_private) VALUES (?, ?, ?, ?)');

// Ayşe'nin fotoğrafları
$insertPhoto->execute([$userIds['azad'], 'beach.svg', 'azadın sulalasınden kalma beach', 1]);
$insertPhoto->execute([$userIds['azad'], 'mountain.svg', 'gabar Dağı', 1]);

// Mehmet'in fotoğrafları
$insertPhoto->execute([$userIds['yagız'], 'city.svg', 'İstanbul en sevdiğim sehir', 1]);
$insertPhoto->execute([$userIds['yagız'], 'cat.svg', 'Kedim', 1]);

$insertPhoto->execute([$userIds['admin'], 'office.svg', 'EN BÜYÜK BURSASPOR', 1]);
$insertPhoto->execute([$userIds['admin'], 'gen_flag.svg', 'EN BÜYÜK BURSASPOR', 1]);

echo "Veritabanı oluşturuldu: {$dbPath}\n";
echo "Kullanıcılar:\n";
foreach ($users as [$uname, $pass, $display]) {
    echo "  - {$uname} / {$pass}  ({$display})\n";
}

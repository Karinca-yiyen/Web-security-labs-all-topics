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
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
');

$pdo->exec('
    CREATE TABLE shares (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        photo_id INTEGER NOT NULL,
        from_user_id INTEGER NOT NULL,
        to_user_id INTEGER NOT NULL,
        invited_username TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT "pending",
        FOREIGN KEY (photo_id) REFERENCES photos(id),
        FOREIGN KEY (to_user_id) REFERENCES users(id)
    )
');

$users = [
    ['azad',   'melekazad',      'Azad Çetin'],
    ['yagiz',  '58sevdalisi',    'Yağız Abuhan'],
    ['admin',  'YonetimPaneli!', 'Sistem Yöneticisi'],
    ['muhtar', 'koyumden1nazar',  'Muhtar Recep Amca'],
];

$userIds = [];
$insertUser = $pdo->prepare('INSERT INTO users (username, password_hash, display_name) VALUES (?, ?, ?)');
foreach ($users as [$uname, $pass, $display]) {
    $insertUser->execute([$uname, password_hash($pass, PASSWORD_DEFAULT), $display]);
    $userIds[$uname] = (int) $pdo->lastInsertId();
}

$insertPhoto = $pdo->prepare('INSERT INTO photos (user_id, filename, title) VALUES (?, ?, ?)');
$photoIds = [];

$insertPhoto->execute([$userIds['azad'], 'beach.svg', "Azad'ın sülalesinden kalma sahil"]);
$photoIds['beach'] = (int) $pdo->lastInsertId();

$insertPhoto->execute([$userIds['azad'], 'mountain.svg', 'Gabar Dağı - favorite spot']);
$photoIds['mountain'] = (int) $pdo->lastInsertId();

$insertPhoto->execute([$userIds['yagiz'], 'city.svg', 'İstanbul - city view']);
$photoIds['city'] = (int) $pdo->lastInsertId();

$insertPhoto->execute([$userIds['yagiz'], 'cat.svg', 'Kedim']);
$photoIds['cat'] = (int) $pdo->lastInsertId();

$insertPhoto->execute([$userIds['admin'], 'office.svg', 'Aylık Yönetim Raporu']);
$photoIds['office'] = (int) $pdo->lastInsertId();

$insertPhoto->execute([$userIds['admin'], 'trophy.svg', 'Yılın En Gizli Ödülü (TOP SECRET, invite only)']);
$photoIds['trophy'] = (int) $pdo->lastInsertId();

$insertShare = $pdo->prepare(
    'INSERT INTO shares (photo_id, from_user_id, to_user_id, invited_username, status) VALUES (?, ?, ?, ?, ?)'
);

// Gerçekçi "gürültü" - zaten kabul edilmiş, sıradan paylaşımlar
$insertShare->execute([$photoIds['beach'], $userIds['azad'], $userIds['yagiz'], 'yagiz', 'accepted']);
$insertShare->execute([$photoIds['cat'], $userIds['yagiz'], $userIds['azad'], 'azad', 'accepted']);

// HEDEF: admin, trophy fotoğrafını SADECE muhtar ile paylaşmak istiyor - henüz kabul edilmemiş
$insertShare->execute([$photoIds['trophy'], $userIds['admin'], $userIds['muhtar'], 'muhtar', 'pending']);

// Ekstra gürültü - admin'in muhtarla paylaştığı, zaten kabul edilmiş sıradan bir dosya
$insertShare->execute([$photoIds['office'], $userIds['admin'], $userIds['muhtar'], 'muhtar', 'accepted']);

echo "Veritabanı oluşturuldu: {$dbPath}\n";
echo "Kullanıcılar:\n";
foreach ($users as [$uname, $pass, $display]) {
    echo "  - {$uname} / {$pass}  ({$display})\n";
}

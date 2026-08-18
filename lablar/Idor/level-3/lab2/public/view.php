<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$user = require_login();

$token = $_GET['token'] ?? '';
$decoded = verify_photo_token($token);

if ($decoded === null) {
    http_response_code(403);
    echo 'Geçersiz veya değiştirilmiş erişim jetonu (imza doğrulanamadı).';
    exit;
}

// Jetondaki kullanıcı, oturum açan kullanıcıyla eşleşiyor mu?
// Bu kontrol her zaman geçer, çünkü jetonu sen kendi hesabınla
// share.php üzerinden ürettin - imza da geçerli. Sorun burada değil.
if ($decoded['u'] !== (int) $user['id']) {
    http_response_code(403);
    echo 'Bu jeton size ait değil.';
    exit;
}

// Fotoğraf sadece id'sine (p) göre çekiliyor. Fotoğrafın GERÇEKTEN
// bu kullanıcıya ait olup olmadığı veritabanında bir daha
// doğrulanmıyor - imzalı jetona güvenildiği için "zaten yetkilendirildi"
// varsayılıyor. Ama share.php bu yetkilendirmeyi hiç yapmamıştı.
$stmt = get_db()->prepare('SELECT * FROM photos WHERE id = ?');
$stmt->execute([$decoded['p']]);
$photo = $stmt->fetch();

if ($photo === null) {
    http_response_code(404);
    echo 'Fotoğraf bulunamadı.';
    exit;
}

$path = __DIR__ . '/../storage/uploads/' . basename($photo['filename']);
if (!file_exists($path)) {
    http_response_code(404);
    echo 'Dosya bulunamadı.';
    exit;
}

header('Content-Type: image/svg+xml');
header('X-Photo-Title: ' . rawurlencode($photo['title']));
readfile($path);

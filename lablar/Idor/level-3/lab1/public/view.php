<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$user = require_login();

$token = $_GET['token'] ?? '';
$decoded = decode_photo_token($token);

if ($decoded === null) {
    http_response_code(400);
    echo 'Geçersiz erişim jetonu.';
    exit;
}

if ($decoded['u'] !== (int) $user['id']) {
    http_response_code(403);
    echo 'Bu fotoğrafa erişim izniniz yok.';
    exit;
}

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

<?php
declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if ($user === null) {
    http_response_code(401);
    echo json_encode(['error' => 'login required']);
    exit;
}

$photoId = (int) ($_GET['photo_id'] ?? 0);
if ($photoId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid photo_id']);
    exit;
}

// Fotoğrafın var olup olmadığı kontrol ediliyor...
$stmt = get_db()->prepare('SELECT id FROM photos WHERE id = ?');
$stmt->execute([$photoId]);
$photo = $stmt->fetch();

if ($photo === null) {
    http_response_code(404);
    echo json_encode(['error' => 'photo not found']);
    exit;
}

// ...ama bu fotoğrafın GERÇEKTEN giriş yapan kullanıcıya (current user)
// ait olup olmadığı hiç sorgulanmıyor! Sunucu, oturum açmış herhangi bir
// kullanıcı için, istenen HERHANGİ bir photo_id'ye geçerli/imzalı bir
// erişim jetonu üretmeyi kabul ediyor.
//
// -> IDOR: broken access control burada, token'ın kendisinde değil.
$token = sign_photo_token((int) $user['id'], $photoId);

echo json_encode(['token' => $token]);

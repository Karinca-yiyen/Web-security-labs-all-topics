<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Geçersiz id.';
    exit;
}

// Bu sorgu düzgün yazılmış: fotoğrafı ya SEN sahibisin (p.user_id = ?)
// ya da sana KABUL EDİLMİŞ bir paylaşımla verilmiş (shares.status='accepted'
// AND shares.to_user_id = ?). Parametreli sorgu, doğru mantık - burada
// bir zafiyet yok. Sorun, "shares.to_user_id" değerinin nasıl atandığında.
$stmt = get_db()->prepare('
    SELECT p.*
    FROM photos p
    WHERE p.id = :id
      AND (
        p.user_id = :uid
        OR EXISTS (
            SELECT 1 FROM shares s
            WHERE s.photo_id = p.id
              AND s.to_user_id = :uid2
              AND s.status = "accepted"
        )
      )
');
$stmt->execute(['id' => $id, 'uid' => $user['id'], 'uid2' => $user['id']]);
$photo = $stmt->fetch();

if ($photo === false) {
    http_response_code(403);
    echo 'Bu fotoğrafa erişim izniniz yok.';
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

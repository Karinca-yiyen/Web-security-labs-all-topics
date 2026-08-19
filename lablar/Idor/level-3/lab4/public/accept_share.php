<?php
declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$user = require_login();

$shareId = (int) ($_GET['share_id'] ?? 0);
if ($shareId <= 0) {
    http_response_code(400);
    echo 'Geçersiz share_id.';
    exit;
}

$db = get_db();

$stmt = $db->prepare('SELECT * FROM shares WHERE id = ? AND status = "pending"');
$stmt->execute([$shareId]);
$share = $stmt->fetch();

if ($share === false) {
    http_response_code(404);
    echo 'Bu davet bulunamadı ya da zaten kabul edilmiş.';
    exit;
}

// BUG: Bu davetin GERÇEKTEN oturum açan kullanıcıya mı ait olduğu
// (share.to_user_id === $user['id']) hiç kontrol edilmiyor.
// Kod, "bu davete tıklayan kişi = davetin sahibi" varsayımıyla
// to_user_id'yi oturum açan kullanıcıya göre YENİDEN YAZIYOR.
// Yani share_id'yi bulan HERKES o daveti kendi üzerine geçirebilir.
$update = $db->prepare('
    UPDATE shares
    SET to_user_id = :uid, status = "accepted"
    WHERE id = :id AND status = "pending"
');
$update->execute(['uid' => $user['id'], 'id' => $shareId]);

header('Location: /dashboard.php?accepted=1');
exit;

<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$user = require_login();
$db = get_db();

$stmt = $db->prepare('SELECT * FROM photos WHERE user_id = ? ORDER BY id');
$stmt->execute([$user['id']]);
$ownPhotos = $stmt->fetchAll();

$stmt = $db->prepare('
    SELECT p.*
    FROM photos p
    JOIN shares s ON s.photo_id = p.id
    WHERE s.to_user_id = ? AND s.status = "accepted"
    ORDER BY p.id
');
$stmt->execute([$user['id']]);
$sharedPhotos = $stmt->fetchAll();

$accepted = isset($_GET['accepted']);
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>karinca-yiyen - My Gallery</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">karinca<span>-yiyen</span></div>
    <div>
        Welcome, <?= htmlspecialchars($user['display_name']) ?> &nbsp;|&nbsp;
        <a href="/logout.php">Logout</a>
    </div>
</div>
<div class="container">

    <?php if ($accepted): ?>
        <div class="success">Davet kabul edildi. Paylaşılan fotoğraf artık galerinde.</div>
    <?php endif; ?>

    <div class="card">
        <h1>Galerim</h1>
        <div class="gallery">
            <?php foreach ($ownPhotos as $photo): ?>
                <div class="photo-card">
                    <a href="/view.php?id=<?= (int) $photo['id'] ?>">
                        <img src="/view.php?id=<?= (int) $photo['id'] ?>" alt="<?= htmlspecialchars($photo['title']) ?>">
                    </a>
                    <div class="meta"><div class="title"><?= htmlspecialchars($photo['title']) ?></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Benimle Paylaşılanlar <span style="font-size:13px;color:#6b7280;font-weight:400;">(shared with me)</span></h2>
        <?php if (empty($sharedPhotos)): ?>
            <p class="empty">Henüz seninle paylaşılan bir şey yok.</p>
        <?php else: ?>
            <div class="gallery">
                <?php foreach ($sharedPhotos as $photo): ?>
                    <div class="photo-card">
                        <a href="/view.php?id=<?= (int) $photo['id'] ?>">
                            <img src="/view.php?id=<?= (int) $photo['id'] ?>" alt="<?= htmlspecialchars($photo['title']) ?>">
                        </a>
                        <div class="meta"><div class="title"><?= htmlspecialchars($photo['title']) ?></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="hint">
            Not: karinca-yiyen'de kullanıcılar birbirine fotoğraf daveti gönderebilir.
            Bekleyen bir davetin varsa, invite linkindeki <code>share_id</code> ile
            <code>/accept_share.php?share_id=...</code> adresinden kabul edebilirsin.
        </p>
    </div>
</div>
</body>
</html>

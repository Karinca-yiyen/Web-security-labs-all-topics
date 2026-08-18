<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$user = require_login();

$stmt = get_db()->prepare('SELECT * FROM photos WHERE user_id = ? ORDER BY id');
$stmt->execute([$user['id']]);
$photos = $stmt->fetchAll();
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
    <div class="card">
        <h1>Galerim</h1>
        <p>Fotoğraflar, tarayıcı tarafından otomatik olarak güvenli (imzalı) linkler
        üzerinden yükleniyor.</p>

        <div class="gallery" id="gallery">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-card" data-photo-id="<?= (int) $photo['id'] ?>">
                    <img src="" alt="<?= htmlspecialchars($photo['title']) ?>" loading="lazy">
                    <div class="meta">
                        <div class="title"><?= htmlspecialchars($photo['title']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="hint">
            Not: Bu galeri, her fotoğraf için önceden hazır bir link göstermiyor -
            sayfa açılınca her kart kendi erişim linkini arka planda ayrıca istiyor.
        </p>
    </div>
</div>

<script>
document.querySelectorAll('.photo-card').forEach(async (card) => {
    const photoId = card.dataset.photoId;
    try {
        const res = await fetch('/share.php?photo_id=' + encodeURIComponent(photoId));
        const data = await res.json();
        if (data.token) {
            card.querySelector('img').src = '/view.php?token=' + encodeURIComponent(data.token);
        }
    } catch (e) {
        console.error('could not load photo', photoId, e);
    }
});
</script>
</body>
</html>

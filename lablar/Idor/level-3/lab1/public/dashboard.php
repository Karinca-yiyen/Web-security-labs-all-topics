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
    <title>Karinca-yiyen - Galerim</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Photo<span>Vault</span></div>
    <div>
        <?= htmlspecialchars($user['display_name']) ?> &nbsp;|&nbsp;
        <a href="/logout.php">exit</a>
    </div>
</div>
<div class="container">
    <div class="card">
        <h1>Galerim</h1>
        <p>Bu, sadece sana ait özel fotoğraf galerin. Her fotoğrafın görüntüleme linki
        sana özel bir erişim jetonu (token) içerir.</p>

        <div class="gallery">
            <?php foreach ($photos as $photo): ?>
                <?php $token = make_photo_token((int) $user['id'], (int) $photo['id']); ?>
                <div class="photo-card">
                    <a href="/view.php?token=<?= urlencode($token) ?>">
                        <img src="/view.php?token=<?= urlencode($token) ?>" alt="<?= htmlspecialchars($photo['title']) ?>">
                    </a>
                    <div class="meta">
                        <div class="title"><?= htmlspecialchars($photo['title']) ?></div>
                        <a href="/view.php?token=<?= urlencode($token) ?>">tam boyutta gör &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="hint">
            EN BÜYÜK BURSASPOR
       </p>
    </div>
</div>
</body>
</html>

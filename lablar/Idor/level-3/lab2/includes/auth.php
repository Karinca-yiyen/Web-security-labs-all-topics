<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = get_db()->prepare('SELECT id, username, display_name FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

/**
 * İmzalı erişim jetonu üretir. Jeton {u, p, sig} içerir; sig,
 * u ve p değerlerinin sunucu tarafı gizli anahtarla HMAC-SHA256
 * imzasıdır. İmza olmadan (ya da yanlış imzayla) view.php jetonu
 * reddeder - yani jetonu elle değiştirmek (p değerini oynamak)
 * imzayı bozar ve işe yaramaz.
 *
 * NOT: Bu fonksiyonun kendisi güvenli. Zafiyet burada değil.
 */
function sign_photo_token(int $userId, int $photoId): string
{
    $payload = ['u' => $userId, 'p' => $photoId];
    $sig = hash_hmac('sha256', $userId . ':' . $photoId, SHARE_SIGNING_SECRET);
    $payload['sig'] = $sig;
    return base64_encode(json_encode($payload));
}

/**
 * Jetonu çözer ve imzayı doğrular. İmza geçersizse null döner.
 */
function verify_photo_token(string $token): ?array
{
    $raw = base64_decode($token, true);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['u'], $data['p'], $data['sig'])) {
        return null;
    }

    $u = (int) $data['u'];
    $p = (int) $data['p'];
    $expectedSig = hash_hmac('sha256', $u . ':' . $p, SHARE_SIGNING_SECRET);

    if (!hash_equals($expectedSig, (string) $data['sig'])) {
        return null; // imza uyuşmuyor - jeton elle değiştirilmiş
    }

    return ['u' => $u, 'p' => $p];
}

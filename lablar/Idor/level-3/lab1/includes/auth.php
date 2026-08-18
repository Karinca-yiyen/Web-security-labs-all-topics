<?php
declare(strict_types=1);

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

function make_photo_token(int $userId, int $photoId): string
{
    $payload = json_encode(['u' => $userId, 'p' => $photoId]);
    return base64_encode($payload);
}

function decode_photo_token(string $token): ?array
{
    $raw = base64_decode($token, true);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['u'], $data['p'])) {
        return null;
    }
    return ['u' => (int) $data['u'], 'p' => (int) $data['p']];
}

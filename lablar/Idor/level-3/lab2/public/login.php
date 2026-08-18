<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (current_user() !== null) {
    header('Location: /dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = get_db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        header('Location: /dashboard.php');
        exit;
    }
    $error = 'Kullanıcı adı veya şifre hatalı.';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>karinca-yiyen - Login</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">karinca<span>-yiyen</span></div>
</div>
<div class="container" style="max-width:420px;">
    <div class="card">
        <h1>Giriş Yap</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Kullanıcı adı</label>
            <input type="text" name="username" autocomplete="username" required>
            <label>Şifre</label>
            <input type="password" name="password" autocomplete="current-password" required>
            <button type="submit">Login</button>
        </form>
        <p class="hint">
            Demo hesap: <strong>azad</strong> / <strong>melekazad</strong>
            &nbsp;veya&nbsp; <strong>yagiz</strong> / <strong>58sevdalisi</strong>
        </p>
    </div>
</div>
</body>
</html>

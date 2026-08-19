<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (current_user() !== null) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit;

<?php
declare(strict_types=1);

/**
 * Bu anahtar SADECE sunucu tarafında kullanılır. Token imzalamak
 * (share.php) ve doğrulamak (view.php) için gerekli. İstemciye hiçbir
 * şekilde gönderilmez, JS dosyalarında da yer almaz.
 */
const SHARE_SIGNING_SECRET = 'k4r1nca-y1yen-2026-cok-gizli-anahtar-sakin-tahmin-etme';

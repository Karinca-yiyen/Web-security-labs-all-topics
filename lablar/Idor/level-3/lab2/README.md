# karinca-yiyen — IDOR Lab (Level 2 / 4 — level3'ten zor)

Level 1 en zor, level 4 en kolay. Bu lab level 2: token'ı direkt
değiştirmek işe yaramıyor, imza doğrulaması var.

## Kurulum

```bash
cd idor-lab
php includes/init_db.php
cd public
php -S 127.0.0.1:1618
```

Demo hesaplar: `azad`/`melekazad`, `yagiz`/`58sevdalisi`

## Görev

`admin`'in "Yılın En Gizli Ödülü (TOP SECRET)" fotoğrafına, admin'e
giriş yapmadan ulaş.

Access `admin`'s "Top Secret Award of the Year" photo without logging in.


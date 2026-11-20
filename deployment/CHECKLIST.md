# Production Serverga Deploy Qilish Nazorat Ro'yxati (Checklist)

Ushbu hujjat **HRD Omborxona Face ID Davomat va Ish Vaqti Hisobi Tizimi**ni jonli production serverga (Linux Ubuntu/Debian, Docker yoki Shared Cpanel/Plesk) xavfsiz va xatosiz o'rnatish bo'yicha qadam-baqadam yo'riqnomadir.

---

## 1. Dastlabki Server Talablari (Prerequisites)
- [x] **OS:** Linux (Ubuntu 22.04 LTS / 24.04 LTS tavsiya etiladi) yoki Docker Engine
- [x] **Web Server:** Nginx 1.20+ yoki Apache 2.4+ (mod_rewrite, mod_headers faol)
- [x] **PHP:** Versiya 8.2 yoki 8.3 (PHP-FPM)
  - Zarur kengaytmalar: `pdo`, `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `gd`, `openssl`, `zip`
- [x] **Ma'lumotlar bazasi:** MySQL 8.0+ yoki MariaDB 10.6+ (utf8mb4_unicode_ci)
- [x] **SSL sertifikat:** HTTPS (Let's Encrypt / Certbot) — brauzer `getUserMedia` kamerasi ishlashi uchun HTTPS majburiydir!

---

## 2. Fayllarni Yuklash va Papka Ruxsatlari
1. Loyiha fayllarini `/var/www/hrd` katalogiga yuklang:
   ```bash
   git clone <repo_url> /var/www/hrd
   cd /var/www/hrd
   ```
2. Kerakli papkalarga yozish huquqini (`chmod / chown`) bering:
   ```bash
   chown -R www-data:www-data /var/www/hrd
   chmod -R 755 /var/www/hrd
   chmod -R 775 /var/www/hrd/uploads
   chmod -R 775 /var/www/hrd/database
   ```
3. `uploads/.htaccess` mavjudligini va unda PHP ijrosi taqiqlanganligini tasdiqlang:
   ```bash
   cat /var/www/hrd/uploads/.htaccess
   ```

---

## 3. Konfiguratsiya (.env)
1. Namuna fayldan nusxa oling:
   ```bash
   cp .env.example .env
   nano .env
   ```
2. Production parametrlarini kiriting:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://davomat.omborxona.uz
   APP_TIMEZONE=Asia/Tashkent

   # MySQL ulanishi
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=hrd_warehouse
   DB_USERNAME=hrd_user
   DB_PASSWORD=KuchliParol123!_#
   DB_CHARSET=utf8mb4

   # Telegram Bot sozlamalari
   TELEGRAM_BOT_ENABLE=true
   TELEGRAM_BOT_TOKEN="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
   TELEGRAM_CHAT_ID="-1001234567890"
   ```

---

## 4. Ma'lumotlar Bazasini Import Qilish
MySQL ma'lumotlar bazasini yarating va sxemani import qiling:
```bash
mysql -u root -p -e "CREATE DATABASE hrd_warehouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'hrd_user'@'localhost' IDENTIFIED BY 'KuchliParol123!_#';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON hrd_warehouse.* TO 'hrd_user'@'localhost'; FLUSH PRIVILEGES;"

# Sxema va 110 ta xodim ma'lumotlarini yuklash:
mysql -u hrd_user -p hrd_warehouse < sql/schema.sql
mysql -u hrd_user -p hrd_warehouse < sql/seed.sql
```

---

## 5. Avtomatlashtirilgan Testlarni Ishga Tushirish (QA Audit)
Serverda barcha qismlar to'g'ri integratsiya bo'lganligini tasdiqlash uchun CLI testini ishga tushiring:
```bash
php tests/run_tests.php
```
Natija: **41 ta test 100% muvaffaqiyatli (0 ta xato)** bo'lishi lozim.

---

## 6. Nginx Konfiguratsiyasi
`/etc/nginx/sites-available/hrd.conf` faylini yarating va `nginx.conf` tarkibidan foydalaning:
```nginx
server {
    listen 80;
    server_name davomat.omborxona.uz;
    return 301 https://$host$request-uri;
}

server {
    listen 443 ssl http2;
    server_name davomat.omborxona.uz;

    ssl_certificate /etc/letsencrypt/live/davomat.omborxona.uz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/davomat.omborxona.uz/privkey.pem;

    root /var/www/hrd;
    index index.php index.html;

    client_max_body_size 50M;

    # Xavfsizlik sarlavhalari
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # .env va yashirin fayllarga kirishni bloklash
    location ~ /\.(env|git|sqlite) {
        deny all;
        return 404;
    }

    # Uploads ichida PHP ijrosini bloklash
    location ~* ^/uploads/.*\.php$ {
        deny all;
        return 403;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```
Nginx ni tekshirib qayta yuklang:
```bash
nginx -t && systemctl reload nginx
```

---

## 7. Yakuniy Tekshiruv (Smoke Tests)
- [ ] Boshqaruv paneli (`https://davomat.omborxona.uz/`) yuklandi va 4 ta kartochka to'g'ri ko'rsatmoqda.
- [ ] Terminal ekrani (`https://davomat.omborxona.uz/terminal.php`) kamerasiga ruxsat berildi va kadr ko'rinmoqda.
- [ ] Sozlamalar bo'limida Telegram Bot ulanishi sinovdan o'tkazildi va test xabar keldi.
- [ ] Sinov tariqasida bitta xodim kirishi amalga oshirildi va Telegram kanalga rasm + matn yetib bordi.

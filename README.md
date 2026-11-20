# HRD Omborxona Face ID Davomat va Ish Vaqti Hisobi Tizimi

100 dan ortiq omborxona ishchilari uchun mo'ljallangan, **sun'iy intellekt API lariga bog'lanmagan**, faqat toza **PHP (8.2+)**, **MySQL / SQLite**, **HTML5/Tailwind CSS/Vanilla JS** va **Telegram Bot API** asosida yaratilgan professional **Face ID Davomat va Ish Vaqti Hisobi Tizimi**.

Vizual interfeys va dizayn tizimi [app.hrd.uz/control-panel/dashboard](https://app.hrd.uz/control-panel/dashboard) uslubida 100% deterministik arxitekturada barpo etilgan.

---

## Asosiy Imkoniyatlar

### 1. Vizual Dizayn va Interfeys (app.hrd.uz uslubida)
- **Yuqori navigatsiya:**
  - **Boshqaruv paneli (`index.php`)**: Kunlik davomat taxtasi.
  - **Tashkilot tuzilmasi (`employees.php`)**: 110 ta ishchi shtati, bo'limlar, PIN kodlar va yangi xodim qo'shish.
  - **Moliya (`finance.php`)**: Haqiqiy ishlangan soatlar bo'yicha hisoblangan ish haqi jamg'armasi va bo'limlar smetasi.
  - **Monitoring (`reports.php`)**: Davomat intizomi foizi (Punctuality rate), kechikishlar dinamikasi va CSV eksport.
  - **Ish jadvallari (`schedules.php`)**: Kunduzgi, ertalabki, kechki va tungi smenalar hamda 15 daqiqalik kechikish toleransi (Grace period).
  - **Kuzatuv (`tracking.php`)**: 12,500 m² omborxona zonasi xaritasi va real-time turniket o'tish jurnali.
  - **Sozlamalar (`settings.php`)**: Telegram bot va kanal integratsiyasi, jonli polling va loglar.
- **4 ta asosiy statistika kartochkasi:**
  - **Barchasi** (Jami 110+ faol ishchi)
  - **Ishda** (Ayni paytda omborda ishlayotganlar)
  - **Kech** (Smena boshlanishidan kechikib kelganlar)
  - **Ishda emas** (Bugun ishga kelmaganlar)
- **Boshqaruv va jadval:**
  - Sana tanlagich (`<`, `>`, Sana inputi, `Bugun` tugmasi)
  - *"+ Qo'lda davomat yaratish"* modal oynasi
  - Status bo'yicha tezkor filtrlar (*Barchasi*, *Ishda*, *Kech*, *Ish tugagan*, *Ishda emas*)
  - Jonli qidiruv (Xodim ismi, familiyasi, tabel kodi va lavozim bo'yicha)
  - Bo'limlar bo'yicha saralash
  - Sahifalash (*Oldingisi*, *1*, *2*, *3*... *Keyingisi*)
  - Ustunlar: *Xodim* (avatar, ism-familiya, tabel kodi, lavozim), *Kirish*, *Chiqish*, *Jami* (ishlangan vaqt), *Ishlanmagan vaqt* (Undertime), *Holat* (rangli badge), *Amallar* (Ko'rish, Tahrirlash, O'chirish).

### 2. Biometrik Face ID Terminal (`terminal.php`)
- Omborxonaning kirish-chiqish darvozasida planshet yoki sensor monitor uchun maxsus kiosk rejimi.
- Veb-kamera (`navigator.mediaDevices.getUserMedia`) orqali real-time yuzni aniqlash va skanerlash lazer animatsiyasi.
- Yuz ko'rinishi bilan avtomatik Kirish (`check_in`) yoki Chiqish (`check_out`) qayd etish (`action=auto`).
- Olingan kadr avtomatik `uploads/attendance/YYYY-MM-DD/` papkasiga saqlanadi.
- Yuz namunalarini 1 click bilan saqlash va yangilash imkoniyati (`api/face_register.php`).
- Yuz aniqlanmagan yoki yorug'lik past bo'lganda 4-xonali PIN / Tabel raqami orqali zaxira kirish-chiqish klaviaturasi.
- Web Audio API orqali muvaffaqiyat/xatolik audio signallari (qo'shimcha mp3 talab qilinmaydi).
- 3 soniyalik avtomatik qayta tayyor bo'lish progress bari.

### 3. Telegram Bot API Integratsiyasi va Jonli Monitoring
- Har bir kirish va chiqishda PHP `cURL` (`CURLFile`) orqali Telegram kanal/guruhga foto va chiroyli HTML hisobot yuboriladi:
  - Kirishda: Xodim ismi, tabel kodi, lavozimi, bo'limi, vaqti, kechikish daqiqasi.
  - Chiqishda: Xodim ismi, tabel kodi, vaqti, *jami ishlangan vaqt* va *ishlanmagan vaqt*.
- **Saytda bot holatining real-time monitoringi:**
  - Bot ulanishi (`getMe` orqali `@username`, nomi, holati).
  - Muvaffaqiyatli va xatolikli yuborishlar soni.
  - Oxirgi yuborilgan xabar vaqti.
  - So'nggi yuborish loglari jadvali.
  - Har 15 soniyada AJAX polling orqali sahifani yangilamasdan avto-yangilanish.
  - Sozlamalar sahifasidan `BOT_TOKEN` va `CHAT_ID` ni o'zgartirish hamda "Test xabar yuborish" tugmasi.

### 4. Aniq Ish Vaqti va Ishlanmagan Vaqt Hisobi (`TimeCalculator`)
- 100% deterministik matematika va aniq vaqt logikasi:
  - `calculateLateMinutes()`: Smena boshlanishi (masalan 09:00) + 15 daqiqa ruxsat chegarasidan kech kelsa, to'liq kechikish daqiqasini hisoblash.
  - `calculateSessionWorkedMinutes()`: Kirish va chiqish orasidagi haqiqiy ishlangan daqiqalar.
  - `calculateTotalWorkedMinutes()`: Bir kunda bir nechta kirish-chiqish sessiyalari bo'yicha umumiy vaqt.
  - `calculateUndertimeMinutes()`: Standart 8 soatlik norma (480 daqiqa) dan kam ishlangan qism.
  - `calculateOvertimeMinutes()`: 480 daqiqadan oshiq ishlangan qism.

---

## Fayllar Tuzilmasi

```
hrd/
├── .env.example              # Konfiguratsiya namunasi
├── .env                      # Mahalliy konfiguratsiya
├── .htaccess                 # Apache xavfsizlik va sarlavhalar
├── docker-compose.yml        # Docker production orkestratsiyasi
├── Dockerfile                # PHP 8.3-FPM konteyner konfiguratsiyasi
├── nginx.conf                # Nginx production konfiguratsiyasi
├── run.bat                   # Windows 1-click ishga tushirish skripti
├── start.sh                  # Linux/macOS 1-click ishga tushirish skripti
├── README.md                 # To'liq hujjatlar
├── sql/
│   ├── schema.sql            # MySQL 8.0+ / MariaDB jadvallar sxemasi
│   └── seed.sql              # 110 ta xodim va namunaviy davomat SQL dump
├── database/
│   ├── schema.sql            # Sxema fayli
│   ├── seed.php              # Seeder dasturi
│   └── warehouse.sqlite      # SQLite zaxira/standalone bazasi
├── config/
│   ├── database.php          # Baza konfiguratsiyasi (MySQL / SQLite)
│   └── telegram.php          # Telegram Bot sozlamalari
├── src/
│   ├── Database.php          # PDO ma'lumotlar bazasi klassi
│   ├── TimeCalculator.php    # Ish vaqti, kechikish va ishlanmagan vaqt hisoblagichi
│   ├── Employee.php          # 110+ xodim modeli va CRUD
│   ├── Attendance.php        # Davomat boshqaruvi va statistika
│   ├── TelegramService.php   # Telegram Bot API (sendPhoto, getMe, loglar)
│   ├── ReportService.php     # Monitoring va CSV eksport xizmati
│   ├── Schedule.php          # Ish jadvallari va smenalar
│   └── Helpers.php           # Xavfsizlik (XSS, CSRF), vaqt va formatlash
├── api/
│   ├── attendance_mark.php   # Face ID va PIN orqali kirish/chiqish qayd etish
│   ├── attendance_list.php   # Davomat ro'yxati va 4 ta kartochka statistikasi
│   ├── bot_status.php        # Telegram Bot real-time monitoring va test xabar
│   ├── face_register.php     # Face ID yuz namunalarini yuklash va ro'yxatdan o'tkazish
│   └── employees.php         # Xodimlar qidiruvi va yangi xodim qo'shish
├── includes/
│   ├── header.php            # HRD.uz dizayn tizimi va Tailwind sarlavhasi
│   ├── navbar.php            # Yuqori navigatsiya paneli
│   └── footer.php            # Pastki qism va Toast bildirishnomalari
├── uploads/
│   ├── .htaccess             # Skriptlar ijrosini taqiqlovchi xavfsizlik qoidasi
│   └── attendance/           # Olingan fotolar (YYYY-MM-DD/)
├── tests/
│   └── run_tests.php         # 41 ta avtomatlashtirilgan QA testlar to'plami
├── deploy/
│   └── CHECKLIST.md          # Production deploy bo'yicha nazorat ro'yxati
├── index.php                 # Boshqaruv paneli (Dashboard)
├── terminal.php              # Face ID Biometrik Kamera Terminali
├── employees.php             # Tashkilot tuzilmasi - Xodimlar
├── finance.php               # Moliya & Ish Haqi hisobi
├── reports.php               # Monitoring & Hisobotlar
├── schedules.php             # Ish jadvallari & Smenalar
├── tracking.php              # Kuzatuv & Omborxona Zonasi
├── settings.php              # Tizim & Telegram Bot sozlamalari
└── test_runner.php           # Web interfeysli QA test paneli
```

---

## Tezkor Ishga Tushirish (Local / Standalone)

### Windows:
1. `run.bat` faylini ikki marta bosing yoki terminalda ishga tushiring:
   ```cmd
   run.bat
   ```
2. Skript avtomatik tarzda:
   - 41 ta QA testlarini o'tkazadi;
   - Local PHP serverini (`http://localhost:8000`) yoqadi;
   - Boshqaruv paneli va Face ID Terminalini brauzeringizda ochadi!

### Linux / macOS:
```bash
chmod +x start.sh
./start.sh
```

---

## Docker yordamida Production Ishga Tushirish

1. Konfiguratsiyani tayyorlang:
   ```bash
   cp .env.example .env
   ```
2. Docker konteynerlarini ko'taring:
   ```bash
   docker-compose up -d --build
   ```
3. Konteynerlar holatini tekshiring:
   ```bash
   docker-compose ps
   ```
4. Saytga kiring:
   - Boshqaruv paneli: `http://localhost:8000`
   - Terminal: `http://localhost:8000/terminal.php`

---

## Avtomatlashtirilgan Testlarni Ishga Tushirish

Tizim o'zida barcha komponentlarni avtomat tekshiruvchi test suite ga ega:
```bash
php tests/run_tests.php
```

Yoki brauzer orqali: `http://localhost:8000/test_runner.php`.

**Tekshiriladigan 41 ta test ssenariysi:**
1. `TimeCalculator` matematik formulalari (o'z vaqtida kelish, kechikish, sessiyalar, undertime, overtime, status).
2. Baza jadvallari va indekslari to'liqligi (`departments`, `schedules`, `employees`, `attendances`, `attendance_logs`, `telegram_logs`, `system_settings`).
3. 110 ta xodim seederi, tabel kodlari (W-1001..W-1110) va 4 ta kartochka statistikasi.
4. Davomat qidiruvi, filtrlash va API ro'yxati.
5. Telegram Bot `getBotStatus()` va offline/mock xavfsiz bildirishnoma qaydi.
6. Base64 rasm dekodlash, papka yaratish va fayl o'lchami tekshiruvi.
7. Xavfsizlik auditi (SQL Injection neytrallanishi, XSS filtri, CSRF token tekshiruvi, `uploads/.htaccess` blokirovkasi).

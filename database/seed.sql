-- HRD Omborxona Davomat Tizimi - Boshlang'ich Ma'lumotlar (MySQL 8.0+ / MariaDB)

INSERT INTO departments (id, name, description) VALUES
(1, 'Ombor ma\'muriyati', 'Ombor boshqaruvi, hisob-kitob va nazorat guruhi'),
(2, 'Qabul qilish bo\'limi', 'Kelgan tovarlarni qabul qilish, yuk xatlarini tekshirish'),
(3, 'Saralash va joylashtirish', 'Tovarlarni qutilarga ajratish va javonlarga terish'),
(4, 'Yuk ortish va tushirish', 'Og\'ir yuklarni tushirish, mashinalarga yuklash guruhi'),
(5, 'Saqlash va inventarizatsiya', 'Tovarlar zaxirasini hisoblash va joylashuvini nazorat qilish'),
(6, 'Qadoqlash va komplektatsiya', 'Buyurtmalarni yig\'ish, qadoqlash va etiketkalash'),
(7, 'Logistika va jo\'natish', 'Yuklarni yo\'nalishlarga taqsimlash va haydovchilarga topshirish'),
(8, 'Xavfsizlik va nazorat', 'Omborxonaning kirish-chiqish nazorati va texnika xavfsizligi');

INSERT INTO schedules (id, name, start_time, end_time, grace_period_minutes, break_minutes, work_days) VALUES
(1, 'Standart kunduzgi smena', '09:00:00', '18:00:00', 15, 60, '1,2,3,4,5,6'),
(2, 'Ertalabki ombor smenasi', '08:00:00', '17:00:00', 15, 60, '1,2,3,4,5,6'),
(3, 'Kechki ombor smenasi', '13:00:00', '22:00:00', 15, 60, '1,2,3,4,5,6'),
(4, 'Tungi smena', '20:00:00', '08:00:00', 15, 60, '1,2,3,4,5,6');

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('telegram_bot_token', '', 'Telegram Bot Token'),
('telegram_chat_id', '', 'Telegram Kanal yoki Guruh ID'),
('telegram_notifications_enabled', '1', 'Telegram orqali rasm va xabar yuborish faol'),
('terminal_camera_active', '1', 'Kamera orqali rasmga olish majburiy'),
('warehouse_title', 'Toshkent Markaziy Logistika Omborxonasi', 'Omborxona rasmiy nomi'),
('late_tolerance_minutes', '15', 'Kechikish uchun berilgan ruxsat vaqti (daqiqa)');

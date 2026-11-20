#!/usr/bin/env bash
# HRD Omborxona Face ID Davomat Tizimi - Linux/macOS Ishga tushirish skripti

set -e

echo "================================================================="
echo "   HRD OMBORXONA FACE ID DAVOMAT VA ISH VAQTI TIZIMI"
echo "================================================================="

# 1. PHP tekshirish
if ! command -v php &> /dev/null; then
    echo "[XATO] PHP topilmadi! Iltimos, PHP 8.2+ o'rnating."
    exit 1
fi

# 2. QA Testlarni o'tkazish
echo "[1/2] QA Avtomatlashtirilgan Testlar o'tkazilmoqda..."
php tests/run_tests.php

# 3. Serverni ishga tushirish
echo "[2/2] Local server ishga tushmoqda: http://localhost:8000"
echo "Boshqaruv paneli: http://localhost:8000"
echo "Face ID Terminal: http://localhost:8000/terminal.php"
echo "To'xtatish uchun: Ctrl+C"

php -S 0.0.0.0:8000

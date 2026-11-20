@echo off
chcp 65001 >nul
title HRD Omborxona Face ID Davomat Tizimi
color 0B

echo =================================================================
echo   HRD OMBORXONA FACE ID DAVOMAT VA ISH VAQTI TIZIMI
echo =================================================================
echo.

:: 1. PHP mavjudligini tekshirish
php -v >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [XATO] PHP tizimda topilmadi! Iltimos, PHP 8.2+ o'rnating.
    pause
    exit /b 1
)

:: 2. Avtomatlashtirilgan QA testlarini ishga tushirish
echo [1/3] QA Avtomatlashtirilgan Testlar o'tkazilmoqda...
php tests/run_tests.php
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [OGOHLANTIRISH] Ba'zi testlar xato berdi. Iltimos, xatolarni tekshiring.
) else (
    echo [OK] 41 ta test muvaffaqiyatli yakunlandi!
)

:: 3. Serverni ishga tushirish
echo.
echo [2/3] Local PHP Server ishga tushirilmoqda: http://localhost:8000
echo [3/3] Brauzerda Boshqaruv paneli ochilmoqda...
start http://localhost:8000/
start http://localhost:8000/terminal.php

echo.
echo =================================================================
echo   Server faol! To'xtatish uchun Ctrl+C bosing.
echo   Boshqaruv paneli: http://localhost:8000/
echo   Face ID Terminal: http://localhost:8000/terminal.php
echo   QA Test Paneli:   http://localhost:8000/test_runner.php
echo =================================================================
echo.

php -S 0.0.0.0:8000

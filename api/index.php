<?php
/**
 * api/index.php - Vercel Serverless Entrypoint / Router
 * Toza marshrutizator: PHP skriptlar va statik resurslarni to'g'ri yetkazib beradi
 */

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = ltrim($uri, '/');

// Standart sahifa
if (empty($uri) || $uri === 'index' || $uri === 'index.html' || $uri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$baseDir = dirname(__DIR__);

// 1. Agar to'g'ridan-to'g'ri loyiha ildizidagi PHP fayl so'ralsa (masalan: login.php, terminal.php, employees.php)
$targetPhp = $baseDir . '/' . $uri;
if (file_exists($targetPhp) && is_file($targetPhp) && str_ends_with($targetPhp, '.php')) {
    require $targetPhp;
    exit;
}

// 2. Agar .php qo'shimchasisiz so'ralgan bo'lsa (masalan: /login -> login.php, /terminal -> terminal.php)
$withPhpExt = $baseDir . '/' . $uri . '.php';
if (file_exists($withPhpExt) && is_file($withPhpExt)) {
    require $withPhpExt;
    exit;
}

// 3. Agar api/ ichidagi boshqa PHP fayllar so'ralgan bo'lsa (masalan: api/attendance_mark.php)
if (str_starts_with($uri, 'api/')) {
    $apiFile = $baseDir . '/' . $uri;
    if (file_exists($apiFile) && is_file($apiFile) && realpath($apiFile) !== realpath(__FILE__)) {
        require $apiFile;
        exit;
    }
}

// 4. Xavfsiz statik fayllar (CSS, JS, rasm) - SQLite va PHP fayllarni berkitish
$staticFile = $baseDir . '/' . $uri;
$blockedExts = ['php', 'sql', 'sqlite', 'env', 'json', 'lock', 'md', 'git'];
$ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));

if (file_exists($staticFile) && is_file($staticFile) && !in_array($ext, $blockedExts, true)) {
    $mimes = [
        'css'  => 'text/css; charset=utf-8',
        'js'   => 'application/javascript; charset=utf-8',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'csv'  => 'text/csv; charset=utf-8',
        'woff2'=> 'font/woff2',
        'woff' => 'font/woff',
        'ttf'  => 'font/ttf',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($staticFile);
    exit;
}

// 5. Standart Boshqaruv paneliga yo'naltirish
require __DIR__ . '/../index.php';

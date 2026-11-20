<?php
/**
 * api/index.php - Vercel Serverless Entrypoint / Router
 */

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = ltrim($uri, '/');

// Standart sahifa
if (empty($uri) || $uri === 'index' || $uri === 'index.html') {
    require __DIR__ . '/../index.php';
    exit;
}

// 1. Agar to'g'ridan-to'g'ri loyiha ildizidagi PHP fayl so'ralsa (masalan: terminal.php, employees.php)
$rootPhp = realpath(__DIR__ . '/../' . $uri);
if ($rootPhp && file_exists($rootPhp) && is_file($rootPhp) && str_ends_with($rootPhp, '.php')) {
    require $rootPhp;
    exit;
}

// 2. Agar .php qo'shimchasisiz so'ralgan bo'lsa (masalan: /terminal -> terminal.php)
$withPhpExt = realpath(__DIR__ . '/../' . $uri . '.php');
if ($withPhpExt && file_exists($withPhpExt) && is_file($withPhpExt)) {
    require $withPhpExt;
    exit;
}

// 3. Agar api/ ichidagi boshqa fayllar so'ralgan bo'lsa (masalan: api/attendance_mark.php)
if (str_starts_with($uri, 'api/')) {
    $apiFile = realpath(__DIR__ . '/../' . $uri);
    if ($apiFile && file_exists($apiFile) && is_file($apiFile) && $apiFile !== __FILE__) {
        require $apiFile;
        exit;
    }
}

// 4. Statik fayllar (CSS, JS, rasm)
$staticFile = realpath(__DIR__ . '/../' . $uri);
if ($staticFile && file_exists($staticFile) && is_file($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'csv' => 'text/csv; charset=utf-8',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($staticFile);
    exit;
}

// 5. Standart Boshqaruv paneliga yo'naltirish
require __DIR__ . '/../index.php';

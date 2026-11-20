<?php
/**
 * logout.php - Tizimdan Xavfsiz Chiqish
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';

$auth = Auth::getInstance();
$auth->logout();

header('Location: login.php');
exit;

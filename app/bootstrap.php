<?php
declare(strict_types=1);

$_sessionPath = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($_sessionPath)) {
    mkdir($_sessionPath, 0777, true);
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_save_path($_sessionPath);
    session_start();
}

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/layout.php';

$pdo = db();

// Compute base URL from document root dynamically
$_docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$_appRoot = realpath(dirname(__DIR__));
$_base = str_replace('\\', '/', substr($_appRoot, strlen($_docRoot)));
define('APP_BASE', rtrim($_base, '/'));
define('APP_PATH', $_appRoot);

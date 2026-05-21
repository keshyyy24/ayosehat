<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$_SESSION = [];
session_destroy();
header('Location: ' . APP_BASE . '/index.php');
exit;

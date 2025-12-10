<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'yatay_gecis');

define('UPLOAD_PATH', __DIR__ . '/public/uploads');
define('BASE_URL', '/');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    exit('Veritabani baglanti hatasi: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

require_once __DIR__ . '/src/roles.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/auth.php';

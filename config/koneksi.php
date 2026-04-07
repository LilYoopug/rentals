<?php

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$db_port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'lenscraft';
$db_user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$db_pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

$koneksi = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int) $db_port);
$db_error = '';

if ($koneksi instanceof mysqli) {
    mysqli_set_charset($koneksi, 'utf8mb4');
} else {
    $db_error = mysqli_connect_error();
}

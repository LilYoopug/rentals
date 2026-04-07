<?php

require_once __DIR__ . '/../data/users/customer-data.php';
require_once __DIR__ . '/../data/activity-data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('register.php');
}

$fullname = trim((string) ($_POST['fullname'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm_password = (string) ($_POST['confirm_password'] ?? '');

if ($fullname === '' || $email === '' || $username === '' || $password === '') {
    set_flash('error', 'Semua field wajib diisi.');
    redirect_to('register.php');
}

if ($password !== $confirm_password) {
    set_flash('error', 'Konfirmasi kata sandi tidak cocok.');
    redirect_to('register.php');
}

if (find_user_by_email($email) || find_user_by_login($username)) {
    set_flash('error', 'Email atau username sudah digunakan.');
    redirect_to('register.php');
}

if (create_customer_user($_POST)) {
    add_activity_log(null, $fullname, 'user', 'registration', 'Membuat akun customer baru.');
    set_flash('success', 'Akun berhasil dibuat dan menunggu persetujuan admin.');
    redirect_to('login.php');
}

set_flash('error', 'Gagal membuat akun.');
redirect_to('register.php');

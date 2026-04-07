<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/users/customer-data.php';
require_once __DIR__ . '/../data/activity-data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_request()) {
    redirect_to('user/security.php');
}

$user = find_user_by_id((int) current_user()['id']);
$current_password = (string) ($_POST['current_password'] ?? '');
$new_password = (string) ($_POST['new_password'] ?? '');
$confirm_password = (string) ($_POST['confirm_password'] ?? '');

if (!$user || !password_verify($current_password, (string) $user['password'])) {
    set_flash('error', 'Kata sandi saat ini tidak valid.');
    redirect_to('user/security.php');
}

if ($new_password === '' || $new_password !== $confirm_password) {
    set_flash('error', 'Konfirmasi kata sandi baru tidak cocok.');
    redirect_to('user/security.php');
}

if (update_customer_password((int) $user['id'], password_hash($new_password, PASSWORD_DEFAULT))) {
    add_activity_log((int) $user['id'], (string) $user['fullname'], (string) $user['role'], 'security', 'Mengubah kata sandi akun.');
    set_flash('success', 'Kata sandi berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui kata sandi.');
}

redirect_to('user/security.php');

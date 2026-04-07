<?php

require_once __DIR__ . '/../data/users/customer-data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('forgot-password.php');
}

$email = trim((string) ($_POST['email'] ?? ''));
$user = find_user_by_email($email);

if ($user) {
    set_flash('success', 'Jika email terdaftar, permintaan verifikasi sudah dicatat. Ubah kata sandi setelah Anda berhasil masuk.');
} else {
    set_flash('success', 'Jika email terdaftar, permintaan verifikasi sudah dicatat.');
}
redirect_to('forgot-password.php');

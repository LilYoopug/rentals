<?php

require_once __DIR__ . '/../data/users/customer-data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Request tidak valid.');
    redirect_to('login.php');
}

$login = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$user = find_user_by_login($login);

if (!$user || !password_verify($password, (string) $user['password'])) {
    $_SESSION['old_input'] = ['username' => $login];
    set_flash('error', 'Username atau kata sandi tidak valid.');
    redirect_to('login.php');
}

session_regenerate_id(true);

$_SESSION['current_user'] = build_session_user_payload($user);

if (db_ready()) {
    db_execute('UPDATE users SET last_active = NOW() WHERE id = ?', [(int) $user['id']]);
}

$product_id = trim((string) ($_POST['product_id'] ?? ''));
$normalized_role = normalize_role_value((string) ($user['role'] ?? ''));

if ($normalized_role === 'admin') {
    redirect_to('admin/index.php');
}

if ($normalized_role === 'petugas') {
    redirect_to('staff/index.php');
}

if ($product_id !== '') {
    redirect_to('product-detail.php?id=' . urlencode($product_id));
}

redirect_to('products.php');

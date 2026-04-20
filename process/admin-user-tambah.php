<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/users/admin-data.php';
require_once __DIR__ . '/../data/activity-data.php';
require_once __DIR__ . '/../includes/upload.php';

$email = trim((string) ($_POST['email'] ?? ''));
$username = trim((string) ($_POST['username'] ?? strtok($email, '@')));
$payload = [
    'fullname' => $_POST['fullname'] ?? '',
    'email' => $email,
    'username' => $username,
    'password' => password_hash((string) ($_POST['password'] ?? 'user123'), PASSWORD_DEFAULT),
    'role' => $_POST['role'] ?? 'pelanggan',
    'status' => $_POST['status'] ?? 'aktif',
    'avatar_path' => save_uploaded_user_avatar('avatar_file', (string) ($_POST['existing_avatar_path'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && create_user_record($payload)) {
    add_activity_log((int) current_user()['id'], (string) current_user()['fullname'], (string) current_user()['role'], 'pelanggan', 'Menambah pelanggan baru.');
    set_flash('success', 'User berhasil ditambahkan.');
} else {
    set_flash('error', 'Gagal menambah user.');
}

redirect_to('admin/users.php');

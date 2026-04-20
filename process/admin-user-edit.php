<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/users/admin-data.php';
require_once __DIR__ . '/../includes/upload.php';

$email = trim((string) ($_POST['email'] ?? ''));
$username = trim((string) ($_POST['username'] ?? strtok($email, '@')));
$payload = $_POST;
$payload['email'] = $email;
$payload['username'] = $username;
$payload['avatar_path'] = save_uploaded_user_avatar('avatar_file', (string) ($_POST['existing_avatar_path'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && update_user_record((int) ($_POST['id'] ?? 0), $payload)) {
    set_flash('success', 'User berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui user.');
}

redirect_to('admin/users.php');

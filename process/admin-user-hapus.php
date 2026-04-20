<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/users/admin-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && delete_user_record((int) ($_POST['id'] ?? 0))) {
    set_flash('success', 'User berhasil dihapus.');
} else {
    set_flash('error', 'Gagal menghapus user.');
}

redirect_to('admin/users.php');

<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/categories-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && update_category_record((int) ($_POST['id'] ?? 0), $_POST)) {
    set_flash('success', 'Kategori berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui kategori.');
}

redirect_to('admin/categories.php');

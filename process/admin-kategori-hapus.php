<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/categories-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && delete_category_record((int) ($_POST['id'] ?? 0))) {
    set_flash('success', 'Kategori berhasil dihapus.');
} else {
    set_flash('error', 'Gagal menghapus kategori.');
}

redirect_to('admin/categories.php');

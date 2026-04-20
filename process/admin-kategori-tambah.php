<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/categories-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && create_category($_POST)) {
    set_flash('success', 'Kategori berhasil ditambahkan.');
} else {
    set_flash('error', 'Gagal menambah kategori.');
}

redirect_to('admin/categories.php');

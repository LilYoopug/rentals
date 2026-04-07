<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/products-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && delete_product_record((int) ($_POST['id'] ?? 0))) {
    set_flash('success', 'Produk berhasil dihapus.');
} else {
    set_flash('error', 'Gagal menghapus produk.');
}

redirect_to('admin/products.php');

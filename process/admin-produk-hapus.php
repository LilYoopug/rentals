<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/products-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['id'] ?? 0);
    
    if ($product_id <= 0) {
        set_flash('error', 'ID produk tidak valid.');
        redirect_to('admin/products.php');
    }
    
    if (delete_product_record($product_id)) {
        set_flash('success', 'Produk berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal menghapus produk. Produk mungkin sedang digunakan dalam peminjaman atau pengembalian aktif.');
    }
} else {
    set_flash('error', 'Metode request tidak valid.');
}

redirect_to('admin/products.php');

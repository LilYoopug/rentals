<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/products-data.php';

$product_id = (int) ($_POST['id'] ?? 0);
$price_per_day = (float) ($_POST['price_per_day'] ?? 0);
$discount_percentage = (int) ($_POST['discount_percentage'] ?? 0);
$stock_total = (int) ($_POST['stock_total'] ?? 0);
$stock_available = (int) ($_POST['stock_available'] ?? 0);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && verify_csrf_request()
    && update_product_stock_and_price($product_id, $price_per_day, $discount_percentage, $stock_total, $stock_available)
) {
    set_flash('success', 'Harga, diskon, dan stok produk berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui harga, diskon, dan stok produk.');
}

redirect_to('staff/stock-price.php');

<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/products-data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Gagal memperbarui harga, diskon, dan stok produk.');
    redirect_to('staff/stock-price.php');
}

$product_ids = $_POST['product_ids'] ?? [];
$price_map = $_POST['price_per_day'] ?? [];
$discount_toggle_map = $_POST['discount_enabled'] ?? [];
$discount_map = $_POST['discount_percentage'] ?? [];
$stock_total_map = $_POST['stock_total'] ?? [];

$updated_count = 0;
$has_error = false;

foreach ($product_ids as $raw_id) {
    $product_id = (int) $raw_id;
    if ($product_id <= 0) {
        continue;
    }

    $price = (float) ($price_map[$product_id] ?? 0);
    $discount = isset($discount_toggle_map[$product_id]) ? (int) ($discount_map[$product_id] ?? 0) : 0;
    $stock_total = (int) ($stock_total_map[$product_id] ?? 0);

    if (!update_product_stock_and_price($product_id, $price, $discount, $stock_total)) {
        $has_error = true;
        break;
    }

    $updated_count++;
}

if (!$has_error && $updated_count > 0) {
    set_flash('success', 'Harga, diskon, dan stok produk berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui harga, diskon, dan stok produk.');
}

redirect_to('staff/stock-price.php');

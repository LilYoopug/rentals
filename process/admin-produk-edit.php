<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/products-data.php';
require_once __DIR__ . '/../includes/upload.php';

$payload = $_POST;
$payload['image_path'] = save_uploaded_product_image('image_file', (string) ($_POST['existing_image_path'] ?? 'images/gear-placeholder.svg'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && update_product_record((int) ($_POST['id'] ?? 0), $payload)) {
    set_flash('success', 'Produk berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui produk.');
}

redirect_to('admin/products.php');

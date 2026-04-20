#!/usr/bin/env bash
set -euo pipefail

php <<'PHP'
<?php
require_once getcwd() . '/includes/functions.php';
require_once getcwd() . '/data/rentals-data.php';
require_once getcwd() . '/data/returns-data.php';

$checks = [
    ['format_currency', format_currency(1234567.89), 'Rp1.234.567,89'],
    ['normalize_role_value_admin', normalize_role_value('admin'), 'admin'],
    ['normalize_role_value_staff', normalize_role_value('petugas'), 'petugas'],
    ['normalize_role_value_user', normalize_role_value('pelanggan'), 'pelanggan'],
    ['normalize_role_value_petugas', normalize_role_value('petugas'), 'petugas'],
    ['normalize_user_status_value', normalize_user_status_value('aktif'), 'aktif'],
    ['normalize_product_status_value', normalize_product_status_value('nonaktif'), 'nonaktif'],
    ['normalize_rental_status_value_pending', normalize_rental_status_value('menunggu'), 'menunggu'],
    ['normalize_rental_status_value_approved', normalize_rental_status_value('approved'), 'aktif'],
    ['normalize_rental_status_value_rejected', normalize_rental_status_value('ditolak'), 'ditolak'],
    ['normalize_return_status_value', normalize_return_status_value('selesai'), 'selesai'],
    ['normalize_delivery_method_value_pickup', normalize_delivery_method_value('ambil_sendiri'), 'ambil_sendiri'],
    ['normalize_delivery_method_value_delivery', normalize_delivery_method_value('diantar'), 'diantar'],
    ['normalize_category_slug_value_mirrorless', normalize_category_slug_value('kamera-mirrorless'), 'kamera-mirrorless'],
    ['normalize_category_slug_value_lens', normalize_category_slug_value('lensa'), 'lensa'],
    ['normalize_login_identifier_petugas', normalize_login_identifier('petugas'), 'staff'],
    ['normalize_login_identifier_pelanggan', normalize_login_identifier('pelanggan'), 'user'],
];

foreach ($checks as [$name, $actual, $expected]) {
    if ($actual !== $expected) {
        fwrite(STDERR, $name . " expected [" . $expected . "] but got [" . $actual . "]\n");
        exit(1);
    }
}

$normalizedRental = normalize_rental_row([
    'rental_code' => 'RENT-TEST',
    'product_id' => 7,
    'product_name' => 'Sony FX3',
    'brand' => 'Sony',
    'category_slug' => 'kamera-mirrorless',
    'start_date' => '2026-04-19',
    'end_date' => '2026-04-20',
    'total_days' => 2,
    'daily_rate' => 150000,
    'discount_percentage' => 0,
    'delivery_method' => 'ambil_sendiri',
    'delivery_fee' => 0,
    'total_price' => 300000,
    'status' => 'menunggu',
]);

if ($normalizedRental['product']['category'] !== 'kamera-mirrorless') {
    fwrite(STDERR, "normalize_rental_row category expected [kamera-mirrorless] but got [" . $normalizedRental['product']['category'] . "]\n");
    exit(1);
}

if ($normalizedRental['deliveryMethod'] !== 'ambil_sendiri') {
    fwrite(STDERR, "normalize_rental_row deliveryMethod expected [ambil_sendiri] but got [" . $normalizedRental['deliveryMethod'] . "]\n");
    exit(1);
}

if ($normalizedRental['status'] !== 'menunggu') {
    fwrite(STDERR, "normalize_rental_row status expected [menunggu] but got [" . $normalizedRental['status'] . "]\n");
    exit(1);
}

$normalizedReturn = normalize_return_row([
    'return_code' => 'RET-TEST',
    'rental_code' => 'RENT-TEST',
    'fullname' => 'Raka Pratama',
    'product_name' => 'Sony FX3',
    'brand' => 'Sony',
    'category_slug' => 'lensa',
    'image_path' => 'uploads/products/sony-fx3.jpg',
    'status' => 'selesai',
    'returned_at' => '2026-04-19 10:00:00',
    'created_at' => '2026-04-19 09:00:00',
    'notes' => 'OK',
]);

if ($normalizedReturn['category'] !== 'lensa') {
    fwrite(STDERR, "normalize_return_row category expected [lensa] but got [" . $normalizedReturn['category'] . "]\n");
    exit(1);
}

if ($normalizedReturn['status'] !== 'selesai') {
    fwrite(STDERR, "normalize_return_row status expected [selesai] but got [" . $normalizedReturn['status'] . "]\n");
    exit(1);
}

echo "OK: Indonesian runtime normalization and Rupiah formatting helpers behave correctly\n";
PHP

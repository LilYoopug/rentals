<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/users/admin-data.php';

$customer = find_user_record_by_fullname((string) ($_POST['customer'] ?? ''));
$user_id = (int) ($_POST['user_id'] ?? ($customer['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0 && create_rental_request($user_id, [
    'product_id' => $_POST['product_id'] ?? 0,
    'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
    'end_date' => $_POST['end_date'] ?? date('Y-m-d'),
    'total_days' => $_POST['total_days'] ?? 1,
    'delivery_method' => $_POST['delivery_method'] ?? 'ambil_sendiri',
])) {
    set_flash('success', 'Peminjaman berhasil ditambahkan.');
} else {
    set_flash('error', 'Gagal menambah peminjaman.');
}

redirect_to('admin/borrowings.php');

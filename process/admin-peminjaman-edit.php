<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/users/admin-data.php';

$customer = find_user_record_by_fullname((string) ($_POST['customer'] ?? ''));
$payload = $_POST;
if ($customer) {
    $payload['user_id'] = (int) $customer['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && update_rental_record((string) ($_POST['rental_code'] ?? ''), $payload)) {
    set_flash('success', 'Status peminjaman berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui peminjaman.');
}

redirect_to('admin/borrowings.php');

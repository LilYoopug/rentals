<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/activity-data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid.']);
    exit;
}

$product_id = (int) ($_POST['product_id'] ?? 0);
$start_date = (string) ($_POST['start_date'] ?? '');
$end_date = (string) ($_POST['end_date'] ?? '');
$delivery_method = (string) ($_POST['delivery_method'] ?? 'ambil_sendiri');

if ($product_id < 1 || $start_date === '' || $end_date === '') {
    echo json_encode(['success' => false, 'message' => 'Data rental belum lengkap.']);
    exit;
}

$days = (new DateTime($start_date))->diff(new DateTime($end_date))->days + 1;

if (!create_rental_request((int) current_user()['id'], [
    'product_id' => $product_id,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'total_days' => $days,
    'delivery_method' => $delivery_method,
])) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan rental.']);
    exit;
}

$rentals = get_customer_rentals((int) current_user()['id']);
$rental = empty($rentals) ? null : $rentals[0];
add_activity_log((int) current_user()['id'], (string) current_user()['fullname'], (string) current_user()['role'], 'rental', 'Membuat permintaan rental.');

echo json_encode(['success' => true, 'rental' => $rental]);

<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/activity-data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_request()) {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid.']);
    exit;
}

$rental_code = (string) ($_POST['rental_code'] ?? '');
if ($rental_code === '') {
    echo json_encode(['success' => false, 'message' => 'Kode rental wajib diisi.']);
    exit;
}

if (!create_return_for_rental($rental_code, (int) current_user()['id'], [
    'expected_user_id' => (int) current_user()['id'],
    'status' => 'menunggu',
    'notes' => 'Pengembalian diajukan dan menunggu persetujuan petugas.',
])) {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pengembalian.']);
    exit;
}

$rentals = get_customer_rentals((int) current_user()['id']);
$rental = null;
foreach ($rentals as $item) {
    if (($item['id'] ?? '') === $rental_code) {
        $rental = $item;
        break;
    }
}

add_activity_log((int) current_user()['id'], (string) current_user()['fullname'], (string) current_user()['role'], 'return', 'Mengajukan pengembalian alat.');
echo json_encode(['success' => true, 'rental' => $rental]);

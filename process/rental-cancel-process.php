<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/activity-data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_request()) {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid.']);
    exit;
}

$rental_code = (string) ($_POST['rental_code'] ?? '');
$rental = find_rental_by_code_for_user($rental_code, (int) current_user()['id']);
if (!$rental || !in_array((string) ($rental['status'] ?? ''), ['pending', 'upcoming', 'active'], true)) {
    echo json_encode(['success' => false, 'message' => 'Rental tidak dapat dibatalkan.']);
    exit;
}

$saved = update_rental_status($rental_code, 'cancelled', [
    'cancelled_at' => date('Y-m-d H:i:s'),
    'cancel_reason' => 'Cancelled by customer',
]);

if (!$saved) {
    echo json_encode(['success' => false, 'message' => 'Gagal membatalkan rental.']);
    exit;
}

add_activity_log((int) current_user()['id'], (string) current_user()['fullname'], (string) current_user()['role'], 'rental', 'Membatalkan rental.');
echo json_encode(['success' => true]);

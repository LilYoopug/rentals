<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/payments-data.php';
require_once __DIR__ . '/../data/activity-data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_request()) {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid.']);
    exit;
}

$rental_code = trim((string) ($_POST['rental_code'] ?? ''));
$method = trim((string) ($_POST['method'] ?? ''));
$card_number = preg_replace('/\D+/', '', trim((string) ($_POST['card_number'] ?? '')));
$card_expiry = preg_replace('/\D+/', '', trim((string) ($_POST['card_expiry'] ?? '')));
$card_cvv = preg_replace('/\D+/', '', trim((string) ($_POST['card_cvv'] ?? '')));

if ($rental_code === '' || $method === '') {
    echo json_encode(['success' => false, 'message' => 'Data pembayaran belum lengkap.']);
    exit;
}

$allowed_methods = ['transfer_bank', 'qris', 'kartu_kredit'];
if (!in_array($method, $allowed_methods, true)) {
    echo json_encode(['success' => false, 'message' => 'Metode pembayaran tidak valid.']);
    exit;
}

if ($method === 'kartu_kredit') {
    if (!preg_match('/^\d{16}$/', $card_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor kartu tidak valid.']);
        exit;
    }

    if (!preg_match('/^\d{4}$/', $card_expiry)) {
        echo json_encode(['success' => false, 'message' => 'Masa berlaku kartu tidak valid.']);
        exit;
    }

    $expiry_month = (int) substr($card_expiry, 0, 2);
    if ($expiry_month < 1 || $expiry_month > 12) {
        echo json_encode(['success' => false, 'message' => 'Masa berlaku kartu tidak valid.']);
        exit;
    }

    if (!preg_match('/^\d{3}$/', $card_cvv)) {
        echo json_encode(['success' => false, 'message' => 'CVV kartu tidak valid.']);
        exit;
    }
}

$user_id = (int) current_user()['id'];
$rental = find_rental_by_code_for_user($rental_code, $user_id);
if (!$rental) {
    echo json_encode(['success' => false, 'message' => 'Rental tidak ditemukan.']);
    exit;
}

if (normalize_rental_status_value((string) ($rental['status'] ?? '')) !== 'disetujui') {
    echo json_encode(['success' => false, 'message' => 'Rental tidak dapat dibayar.']);
    exit;
}

$payment = find_payment_by_rental_id((int) ($rental['id'] ?? 0));
if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Tagihan pembayaran tidak ditemukan.']);
    exit;
}

if ((string) ($payment['status'] ?? '') === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Pembayaran sudah diproses.']);
    exit;
}

db_begin_transaction();

try {
    $paid_payment = mark_payment_as_paid((int) $rental['id'], [
        'method' => $method,
    ]);

    if (!$paid_payment) {
        throw new RuntimeException('Gagal memperbarui pembayaran.');
    }

    $activated = update_rental_status($rental_code, 'aktif');
    if (!$activated) {
        throw new RuntimeException('Gagal mengaktifkan rental.');
    }

    db_commit_transaction();
} catch (Throwable $exception) {
    db_rollback_transaction();
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pembayaran.']);
    exit;
}

$rentals = get_customer_rentals($user_id);
$updated_rental = null;
foreach ($rentals as $item) {
    if (($item['id'] ?? '') === $rental_code) {
        $updated_rental = $item;
        break;
    }
}

add_activity_log($user_id, (string) current_user()['fullname'], (string) current_user()['role'], 'payment', 'Menyelesaikan pembayaran rental.');

echo json_encode([
    'success' => true,
    'rental' => $updated_rental,
    'payment' => [
        'status' => 'paid',
        'method' => $method,
    ],
]);

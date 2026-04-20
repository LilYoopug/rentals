<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../data/payments-data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_request()) {
    set_flash('error', 'Gagal menyetujui peminjaman.');
    redirect_to('staff/borrowings.php');
}

$rental_code = (string) ($_POST['rental_code'] ?? '');
$rental = find_rental_by_code($rental_code);

if (!$rental || normalize_rental_status_value((string) ($rental['status'] ?? '')) !== 'menunggu') {
    set_flash('error', 'Peminjaman tidak dapat disetujui.');
    redirect_to('staff/borrowings.php');
}

db_begin_transaction();

try {
    $approved = update_rental_status($rental_code, 'disetujui', ['approved_at' => date('Y-m-d H:i:s')]);
    if (!$approved) {
        throw new RuntimeException('Gagal memperbarui status rental.');
    }

    $approved_rental = find_rental_by_code($rental_code);
    $payment = $approved_rental ? ensure_pending_payment_for_rental($approved_rental) : false;
    if (!$payment) {
        throw new RuntimeException('Gagal membuat pembayaran pending.');
    }

    db_commit_transaction();
    set_flash('success', 'Peminjaman disetujui dan menunggu pembayaran.');
} catch (Throwable $exception) {
    db_rollback_transaction();
    set_flash('error', 'Gagal menyetujui peminjaman.');
}

redirect_to('staff/borrowings.php');

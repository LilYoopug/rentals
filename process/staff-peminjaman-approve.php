<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/rentals-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && update_rental_status((string) ($_POST['rental_code'] ?? ''), 'active', ['approved_at' => date('Y-m-d H:i:s')])) {
    set_flash('success', 'Peminjaman disetujui.');
} else {
    set_flash('error', 'Gagal menyetujui peminjaman.');
}

redirect_to('staff/borrowings.php');

<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/rentals-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && update_rental_status((string) ($_POST['rental_code'] ?? ''), 'dibatalkan', [
    'cancelled_at' => date('Y-m-d H:i:s'),
    'cancel_reason' => 'Rejected by staff',
])) {
    set_flash('success', 'Peminjaman ditolak.');
} else {
    set_flash('error', 'Gagal menolak peminjaman.');
}

redirect_to('staff/borrowings.php');

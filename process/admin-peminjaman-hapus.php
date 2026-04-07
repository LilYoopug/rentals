<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/rentals-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && delete_rental_record((string) ($_POST['rental_code'] ?? ''))) {
    set_flash('success', 'Peminjaman berhasil dihapus.');
} else {
    set_flash('error', 'Gagal menghapus peminjaman.');
}

redirect_to('admin/borrowings.php');

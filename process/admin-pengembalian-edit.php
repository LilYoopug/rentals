<?php

require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../data/rentals-data.php';

$return_code = (string) ($_POST['return_code'] ?? '');
$rental_code = (string) ($_POST['rental_code'] ?? '');
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && $return_code !== '') {
    $saved = update_return_record($return_code, [
        'notes' => $_POST['notes'] ?? '',
        'status' => ($_POST['status'] ?? 'selesai') === 'menunggu' ? 'menunggu' : 'selesai',
        'processed_by' => current_user()['id'] ?? null,
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_request() && $rental_code !== '') {
    $saved = create_return_for_rental($rental_code, (int) (current_user()['id'] ?? 0), [
        'status' => ($_POST['status'] ?? 'selesai') === 'menunggu' ? 'menunggu' : 'selesai',
        'notes' => $_POST['notes'] ?? 'Returned through admin flow.',
    ]);
}

if ($saved) {
    set_flash('success', 'Pengembalian berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui pengembalian.');
}

redirect_to('admin/returns.php');

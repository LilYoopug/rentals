<?php

require_once __DIR__ . '/../includes/staff-check.php';
require_once __DIR__ . '/../data/returns-data.php';

$rental_code = (string) ($_POST['rental_code'] ?? '');
$return_code = (string) ($_POST['return_code'] ?? '');
$status = (string) ($_POST['status'] ?? 'selesai');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Request tidak valid.');
    redirect_to('staff/returns.php');
}

if ($rental_code === '' && $return_code !== '') {
    foreach (get_all_returns() as $row) {
        if (($row['id'] ?? '') === $return_code) {
            $rental_code = (string) ($row['rentalCode'] ?? '');
            break;
        }
    }
}

if ($return_code !== '') {
    $saved = update_return_record($return_code, [
        'status' => $status === 'menunggu' ? 'menunggu' : 'selesai',
        'notes' => 'Pengembalian dikonfirmasi oleh petugas.',
        'processed_by' => (int) current_user()['id'],
    ]);
} else {
    $saved = $rental_code !== '' && create_return_for_rental($rental_code, (int) current_user()['id'], [
        'status' => $status === 'menunggu' ? 'menunggu' : 'selesai',
        'notes' => 'Pengembalian dikonfirmasi oleh petugas.',
    ]);
}

if ($saved) {
    set_flash('success', 'Pengembalian berhasil dikonfirmasi.');
} else {
    set_flash('error', 'Gagal mengonfirmasi pengembalian.');
}

redirect_to('staff/returns.php');

<?php

require_once __DIR__ . '/../includes/functions.php';

function build_payment_code()
{
    return 'PAY-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function build_payment_reference_code()
{
    return 'REF-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function find_payment_by_rental_id($rental_id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM payments WHERE rental_id = ? LIMIT 1', [(int) $rental_id]);
}

function find_payment_by_rental_code($rental_code)
{
    if (!db_ready()) {
        return null;
    }

    return db_one(
        'SELECT p.*
         FROM payments p
         JOIN rentals r ON r.id = p.rental_id
         WHERE r.rental_code = ?
         LIMIT 1',
        [$rental_code]
    );
}

function ensure_pending_payment_for_rental($rental_row, $method = 'transfer_bank')
{
    if (!db_ready()) {
        return false;
    }

    $rental_id = (int) ($rental_row['id'] ?? 0);
    if ($rental_id < 1) {
        return false;
    }

    $existing = find_payment_by_rental_id($rental_id);
    if ($existing) {
        return $existing;
    }

    $saved = db_execute(
        'INSERT INTO payments (payment_code, rental_id, amount, method, status, created_at, updated_at) VALUES (?, ?, ?, ?, "pending", NOW(), NOW())',
        [
            build_payment_code(),
            $rental_id,
            (float) ($rental_row['total_price'] ?? 0),
            trim((string) $method) !== '' ? trim((string) $method) : 'transfer_bank',
        ]
    );

    if (!$saved) {
        return false;
    }

    return find_payment_by_rental_id($rental_id);
}

function mark_payment_as_paid($rental_id, $payment_data = [])
{
    if (!db_ready()) {
        return false;
    }

    $payment = find_payment_by_rental_id((int) $rental_id);
    if (!$payment) {
        return false;
    }

    if ((string) ($payment['status'] ?? '') === 'paid') {
        return $payment;
    }

    $saved = db_execute(
        'UPDATE payments
         SET method = ?, status = "paid", payer_name = ?, payer_email = ?, payer_phone = ?, reference_code = ?, paid_at = ?, updated_at = NOW()
         WHERE rental_id = ?',
        [
            trim((string) ($payment_data['method'] ?? $payment['method'] ?? 'transfer_bank')) ?: 'transfer_bank',
            trim((string) ($payment_data['payer_name'] ?? '')) ?: null,
            trim((string) ($payment_data['payer_email'] ?? '')) ?: null,
            trim((string) ($payment_data['payer_phone'] ?? '')) ?: null,
            trim((string) ($payment_data['reference_code'] ?? build_payment_reference_code())) ?: build_payment_reference_code(),
            $payment_data['paid_at'] ?? date('Y-m-d H:i:s'),
            (int) $rental_id,
        ]
    );

    if (!$saved) {
        return false;
    }

    return find_payment_by_rental_id((int) $rental_id);
}

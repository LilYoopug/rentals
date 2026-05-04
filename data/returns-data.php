<?php

require_once __DIR__ . '/rentals-data.php';
require_once __DIR__ . '/../includes/trigger-functions.php';

function normalize_return_row($row)
{
    return [
        'id' => (string) ($row['return_code'] ?? ''),
        'rentalCode' => (string) ($row['rental_code'] ?? ''),
        'fullname' => (string) ($row['fullname'] ?? ''),
        'productName' => (string) ($row['product_name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => normalize_category_slug_value((string) ($row['category_slug'] ?? '')),
        'image' => (string) ($row['image_path'] ?? 'images/gear-placeholder.svg'),
        'fineAmount' => (float) ($row['fine_amount'] ?? 0),
        'status' => normalize_return_status_value((string) ($row['status'] ?? 'menunggu')),
        'returnedAt' => !empty($row['returned_at']) ? date('Y-m-d', strtotime((string) $row['returned_at'])) : null,
        'createdAt' => !empty($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : '',
        'notes' => (string) ($row['notes'] ?? ''),
    ];
}

function find_return_by_code($return_code)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM returns WHERE return_code = ? LIMIT 1', [$return_code]);
}

function find_return_by_rental_id($rental_id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM returns WHERE rental_id = ? LIMIT 1', [(int) $rental_id]);
}

function get_all_returns()
{
    if (!db_ready()) {
        return [];
    }

    return array_map(
        'normalize_return_row',
        db_all(
            'SELECT rt.*, r.rental_code, u.fullname, p.name AS product_name, p.brand, p.category_slug, p.image_path
             FROM returns rt
             JOIN rentals r ON r.id = rt.rental_id
             JOIN users u ON u.id = r.user_id
             JOIN products p ON p.id = r.product_id
             ORDER BY rt.created_at DESC'
        )
    );
}

function get_customer_returns($user_id)
{
    if (!db_ready()) {
        return [];
    }

    return array_map(
        'normalize_return_row',
        db_all(
            'SELECT rt.*, r.rental_code, u.fullname, p.name AS product_name, p.brand, p.category_slug, p.image_path
             FROM returns rt
             JOIN rentals r ON r.id = rt.rental_id
             JOIN users u ON u.id = r.user_id
             JOIN products p ON p.id = r.product_id
             WHERE r.user_id = ?
             ORDER BY rt.created_at DESC',
            [(int) $user_id]
        )
    );
}

function get_return_tracking_rows()
{
    if (!db_ready()) {
        return [];
    }

    return db_all(
        'SELECT
             COALESCE(rt.return_code, r.rental_code) AS tracking_id,
             rt.return_code,
             rt.notes,
             COALESCE(rt.fine_amount, 0) AS fine_amount,
             rt.returned_at,
             r.rental_code,
             r.start_date,
             r.end_date,
             r.total_days,
             r.daily_rate,
             r.discount_percentage,
             r.total_price,
             r.delivery_method,
             r.delivery_fee,
             u.fullname,
             p.name AS product_name,
             p.brand,
             p.category_slug,
             p.image_path,
             CASE
                 WHEN rt.id IS NULL THEN "dipinjam"
                 ELSE rt.status
             END AS status,
             COALESCE(rt.created_at, r.created_at) AS tracking_created_at,
             COALESCE(rt.id, r.id) AS tracking_sort_id
         FROM rentals r
         LEFT JOIN returns rt ON rt.rental_id = r.id
         JOIN users u ON u.id = r.user_id
         JOIN products p ON p.id = r.product_id
         WHERE rt.id IS NOT NULL OR (r.status IN ("aktif", "active") AND rt.id IS NULL)
         ORDER BY
             CASE
                 WHEN rt.id IS NULL THEN 0
                 WHEN rt.status IN ("menunggu", "pending") THEN 1
                 ELSE 2
             END ASC,
             tracking_created_at ASC,
             tracking_sort_id ASC'
    );
}

function create_return_for_rental($rental_code, $processed_by = null, $options = [])
{
    if (!db_ready()) {
        return false;
    }

    $rental = find_rental_by_code($rental_code);
    if (!$rental) {
        return false;
    }

    if (!empty($options['expected_user_id']) && (int) $rental['user_id'] !== (int) $options['expected_user_id']) {
        return false;
    }

    if (normalize_rental_status_value((string) ($rental['status'] ?? '')) !== 'aktif') {
        return false;
    }

    if (find_return_by_rental_id((int) $rental['id']) !== null) {
        return false;
    }

    $return_status = normalize_return_status_value((string) ($options['status'] ?? 'menunggu'));
    $stored_return_status = storage_return_status_value($return_status);

    $notes = trim((string) ($options['notes'] ?? 'Pengembalian diajukan melalui website.'));

    // Call trigger function before insert to calculate fine and returned_at
    $trigger_data = trigger_return_before_insert(
        (int) $rental['id'],
        $return_status,
        $options['returned_at'] ?? null
    );

    $result = db_execute(
        'INSERT INTO returns (return_code, rental_id, processed_by, notes, fine_amount, status, returned_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            build_return_code(),
            (int) $rental['id'],
            $processed_by ? (int) $processed_by : null,
            $notes,
            $trigger_data['fine_amount'],
            $stored_return_status,
            $trigger_data['returned_at'],
        ]
    );

    // Call trigger function after insert to update rental status
    if ($result) {
        trigger_return_after_insert(
            (int) $rental['id'],
            $return_status,
            $trigger_data['returned_at']
        );
    }

    return $result;
}

function update_return_record($return_code, $data)
{
    if (!db_ready()) {
        return false;
    }

    $return_row = find_return_by_code($return_code);
    if (!$return_row) {
        return false;
    }

    $rental = db_one(
        'SELECT r.*, p.id AS product_id
         FROM rentals r
         JOIN returns rt ON rt.rental_id = r.id
         JOIN products p ON p.id = r.product_id
         WHERE rt.return_code = ? LIMIT 1',
        [$return_code]
    );
    if (!$rental) {
        return false;
    }

    $old_status = normalize_return_status_value((string) ($return_row['status'] ?? 'menunggu'));
    $new_status = normalize_return_status_value((string) ($data['status'] ?? $old_status));

    if ($old_status === 'selesai' && $new_status !== 'selesai') {
        return false;
    }

    $stored_new_status = storage_return_status_value($new_status);

    // Call trigger function before update to calculate fine and returned_at
    $trigger_data = trigger_return_before_update(
        (int) $rental['id'],
        $new_status,
        $return_row['returned_at'],
        $data['returned_at'] ?? null
    );

    $result = db_execute(
        'UPDATE returns
         SET notes = ?, fine_amount = ?, status = ?, processed_by = ?, returned_at = ?
         WHERE return_code = ?',
        [
            trim((string) ($data['notes'] ?? '')),
            $trigger_data['fine_amount'],
            $stored_new_status,
            !empty($data['processed_by']) ? (int) $data['processed_by'] : null,
            $trigger_data['returned_at'],
            $return_code,
        ]
    );

    // Call trigger function after update to update rental status
    if ($result) {
        trigger_return_after_update(
            (int) $rental['id'],
            $old_status,
            $new_status,
            $trigger_data['returned_at']
        );
    }

    return $result;
}

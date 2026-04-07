<?php

require_once __DIR__ . '/rentals-data.php';

function normalize_return_row($row)
{
    return [
        'id' => (string) ($row['return_code'] ?? ''),
        'rentalCode' => (string) ($row['rental_code'] ?? ''),
        'fullname' => (string) ($row['fullname'] ?? ''),
        'productName' => (string) ($row['product_name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => (string) ($row['category_slug'] ?? ''),
        'image' => (string) ($row['image_path'] ?? 'images/gear-placeholder.svg'),
        'status' => (string) ($row['status'] ?? 'pending'),
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

    if (($rental['status'] ?? '') !== 'active') {
        return false;
    }

    if (find_return_by_rental_id((int) $rental['id']) !== null) {
        return false;
    }

    $return_status = trim((string) ($options['status'] ?? 'completed'));
    if (!in_array($return_status, ['pending', 'completed'], true)) {
        $return_status = 'completed';
    }

    $notes = trim((string) ($options['notes'] ?? 'Returned through website flow.'));

    return db_execute(
        'INSERT INTO returns (return_code, rental_id, processed_by, notes, status, returned_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [
            build_return_code(),
            (int) $rental['id'],
            $processed_by ? (int) $processed_by : null,
            $notes,
            $return_status,
            $return_status === 'completed' ? date('Y-m-d H:i:s') : null,
        ]
    );
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

    $old_status = trim((string) ($return_row['status'] ?? 'pending'));
    $new_status = trim((string) ($data['status'] ?? $old_status));
    if (!in_array($new_status, ['pending', 'completed'], true)) {
        $new_status = $old_status;
    }

    if ($old_status === 'completed' && $new_status !== 'completed') {
        return false;
    }

    return db_execute(
        'UPDATE returns
         SET notes = ?, status = ?, processed_by = ?, returned_at = CASE WHEN ? = "completed" THEN COALESCE(returned_at, NOW()) ELSE NULL END
         WHERE return_code = ?',
        [
            trim((string) ($data['notes'] ?? '')),
            $new_status,
            !empty($data['processed_by']) ? (int) $data['processed_by'] : null,
            $new_status,
            $return_code,
        ]
    );
}

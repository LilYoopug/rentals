<?php

require_once __DIR__ . '/products-data.php';

function normalize_rental_status_input($status)
{
    $status = trim((string) $status);

    $map = [
        'approved' => 'disetujui',
        'ditolak' => 'ditolak',
    ];

    $normalized = $map[$status] ?? $status;
    $allowed = ['menunggu', 'disetujui', 'mendatang', 'aktif', 'selesai', 'dibatalkan', 'ditolak'];

    return in_array($normalized, $allowed, true) ? $normalized : 'menunggu';
}

function rental_transition_metadata($status, $current_row, $extra = [])
{
    $status = normalize_rental_status_input($status);

    return [
        'approved_at' => in_array($status, ['disetujui', 'aktif'], true)
            ? ($extra['approved_at'] ?? $current_row['approved_at'] ?? date('Y-m-d H:i:s'))
            : ($extra['approved_at'] ?? $current_row['approved_at']),
        'completed_at' => $status === 'selesai'
            ? ($extra['completed_at'] ?? date('Y-m-d H:i:s'))
            : ($extra['completed_at'] ?? $current_row['completed_at']),
        'cancelled_at' => in_array($status, ['dibatalkan', 'ditolak'], true)
            ? ($extra['cancelled_at'] ?? date('Y-m-d H:i:s'))
            : ($extra['cancelled_at'] ?? $current_row['cancelled_at']),
        'cancel_reason' => in_array($status, ['dibatalkan', 'ditolak'], true)
            ? ($extra['cancel_reason'] ?? $current_row['cancel_reason'])
            : null,
    ];
}

function normalize_rental_row($row)
{
    $status = normalize_rental_status_value((string) ($row['status'] ?? 'menunggu'));
    $return_status = trim((string) ($row['return_status'] ?? ''));

    if (
        $return_status !== ''
        && normalize_return_status_value($return_status) === 'menunggu'
        && !in_array($status, ['selesai', 'dibatalkan', 'ditolak'], true)
    ) {
        $status = 'menunggu';
    }

    return [
        'id' => (string) ($row['rental_code'] ?? ''),
        'dbId' => (int) ($row['id'] ?? 0),
        'product' => [
            'id' => (int) ($row['product_id'] ?? 0),
            'name' => (string) ($row['product_name'] ?? ''),
            'brand' => (string) ($row['brand'] ?? ''),
            'category' => normalize_category_slug_value((string) ($row['category_slug'] ?? '')),
            'image' => public_media_path((string) ($row['image_path'] ?? 'images/gear-placeholder.svg')),
        ],
        'startDate' => (string) ($row['start_date'] ?? ''),
        'endDate' => (string) ($row['end_date'] ?? ''),
        'totalDays' => (int) ($row['total_days'] ?? 1),
        'dailyRate' => (float) ($row['daily_rate'] ?? 0),
        'discount' => (int) ($row['discount_percentage'] ?? 0),
        'deliveryMethod' => normalize_delivery_method_value((string) ($row['delivery_method'] ?? 'ambil_sendiri')),
        'deliveryFee' => (float) ($row['delivery_fee'] ?? 0),
        'total' => (float) ($row['total_price'] ?? 0),
        'status' => $status,
        'payment' => [
            'code' => (string) ($row['payment_code'] ?? ''),
            'status' => (string) ($row['payment_status'] ?? ''),
            'method' => (string) ($row['payment_method'] ?? ''),
            'referenceCode' => (string) ($row['payment_reference_code'] ?? ''),
            'paidAt' => !empty($row['payment_paid_at']) ? date('Y-m-d', strtotime((string) $row['payment_paid_at'])) : null,
        ],
        'createdAt' => !empty($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : '',
        'approvedAt' => !empty($row['approved_at']) ? date('Y-m-d', strtotime((string) $row['approved_at'])) : null,
        'completedAt' => !empty($row['completed_at']) ? date('Y-m-d', strtotime((string) $row['completed_at'])) : null,
        'cancelledAt' => !empty($row['cancelled_at']) ? date('Y-m-d', strtotime((string) $row['cancelled_at'])) : null,
        'cancelReason' => (string) ($row['cancel_reason'] ?? ''),
    ];
}

function get_customer_rentals($user_id)
{
    if (!db_ready()) {
        return [];
    }

    return array_map(
        'normalize_rental_row',
        db_all(
            'SELECT r.*, p.name AS product_name, p.brand, p.category_slug, p.image_path, rt.status AS return_status,
                    pay.payment_code, pay.status AS payment_status, pay.method AS payment_method,
                    pay.reference_code AS payment_reference_code, pay.paid_at AS payment_paid_at
             FROM rentals r
             JOIN products p ON p.id = r.product_id
             LEFT JOIN returns rt ON rt.rental_id = r.id
             LEFT JOIN payments pay ON pay.rental_id = r.id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC',
            [(int) $user_id]
        )
    );
}

function get_customer_rental_detail($user_id, $rental_code)
{
    if (!db_ready()) {
        return null;
    }

    $row = db_one(
        'SELECT r.*, p.name AS product_name, p.brand, p.category_slug, p.image_path, rt.status AS return_status,
                pay.payment_code, pay.status AS payment_status, pay.method AS payment_method,
                pay.reference_code AS payment_reference_code, pay.paid_at AS payment_paid_at
         FROM rentals r
         JOIN products p ON p.id = r.product_id
         LEFT JOIN returns rt ON rt.rental_id = r.id
         LEFT JOIN payments pay ON pay.rental_id = r.id
         WHERE r.user_id = ? AND r.rental_code = ?
         LIMIT 1',
        [(int) $user_id, $rental_code]
    );

    return $row ? normalize_rental_row($row) : null;
}

function get_all_borrowings()
{
    if (!db_ready()) {
        return [];
    }

    return db_all(
        'SELECT r.*, u.fullname, p.name AS product_name, p.brand, p.category_slug, p.image_path
         FROM rentals r
         JOIN users u ON u.id = r.user_id
         JOIN products p ON p.id = r.product_id
         ORDER BY r.created_at DESC'
    );
}

function find_rental_by_code($rental_code)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM rentals WHERE rental_code = ? LIMIT 1', [$rental_code]);
}

function find_rental_by_code_for_user($rental_code, $user_id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one(
        'SELECT * FROM rentals WHERE rental_code = ? AND user_id = ? LIMIT 1',
        [$rental_code, (int) $user_id]
    );
}

function create_rental_request($user_id, $data)
{
    if (!db_ready()) {
        return false;
    }

    $product = get_product_row((int) ($data['product_id'] ?? 0));
    if (!$product || normalize_product_status_value((string) ($product['status'] ?? 'nonaktif')) !== 'aktif') {
        return false;
    }

    $start_date = (string) ($data['start_date'] ?? date('Y-m-d'));
    $end_date = (string) ($data['end_date'] ?? date('Y-m-d'));
    if ($start_date === '' || $end_date === '' || strtotime($end_date) < strtotime($start_date)) {
        return false;
    }

    $total_days = max(1, (int) ($data['total_days'] ?? 1));
    $delivery_method = trim((string) ($data['delivery_method'] ?? 'ambil_sendiri'));
    $delivery_fee = $delivery_method === 'diantar' ? 50000 : 0;
    $daily_rate = product_daily_rate($product);
    $total_price = ($daily_rate * $total_days) + $delivery_fee;

    return db_execute(
        'INSERT INTO rentals (rental_code, user_id, product_id, start_date, end_date, total_days, daily_rate, discount_percentage, delivery_method, delivery_fee, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "menunggu", NOW())',
        [
            build_rental_code(),
            (int) $user_id,
            (int) $product['id'],
            $start_date,
            $end_date,
            $total_days,
            $daily_rate,
            (int) ($product['discount_percentage'] ?? 0),
            $delivery_method,
            $delivery_fee,
            $total_price,
        ]
    );
}

function update_rental_status($rental_code, $status, $extra = [])
{
    if (!db_ready()) {
        return false;
    }

    $rental = find_rental_by_code($rental_code);
    if (!$rental) {
        return false;
    }

    $new_status = normalize_rental_status_input($status);
    $metadata = rental_transition_metadata($new_status, $rental, $extra);

    return db_execute(
        'UPDATE rentals SET status = ?, approved_at = ?, completed_at = ?, cancelled_at = ?, cancel_reason = ? WHERE rental_code = ?',
        [
            $new_status,
            $metadata['approved_at'],
            $metadata['completed_at'],
            $metadata['cancelled_at'],
            $metadata['cancel_reason'],
            $rental_code,
        ]
    );
}

function update_rental_record($rental_code, $data)
{
    if (!db_ready()) {
        return false;
    }

    $rental = find_rental_by_code($rental_code);
    if (!$rental) {
        return false;
    }

    $product_id = (int) ($data['product_id'] ?? $rental['product_id']);
    $product = get_product_row($product_id);
    if (!$product || normalize_product_status_value((string) ($product['status'] ?? 'nonaktif')) !== 'aktif') {
        return false;
    }

    $status = normalize_rental_status_input($data['status'] ?? $rental['status']);
    $start_date = (string) ($data['start_date'] ?? $rental['start_date']);
    $end_date = (string) ($data['end_date'] ?? $rental['end_date']);
    if ($start_date === '' || $end_date === '' || strtotime($end_date) < strtotime($start_date)) {
        return false;
    }

    $total_days = max(1, (int) ($data['total_days'] ?? $rental['total_days']));
    $delivery_method = trim((string) ($data['delivery_method'] ?? $rental['delivery_method'] ?? 'ambil_sendiri'));
    $delivery_fee = $delivery_method === 'diantar' ? 50000 : 0;
    $daily_rate = product_daily_rate($product);
    $total_price = ($daily_rate * $total_days) + $delivery_fee;
    $metadata = rental_transition_metadata($status, $rental, $data);

    return db_execute(
        'UPDATE rentals
         SET user_id = ?, product_id = ?, start_date = ?, end_date = ?, total_days = ?, daily_rate = ?, discount_percentage = ?, delivery_method = ?, delivery_fee = ?, total_price = ?, status = ?, approved_at = ?, completed_at = ?, cancelled_at = ?, cancel_reason = ?
         WHERE rental_code = ?',
        [
            (int) ($data['user_id'] ?? $rental['user_id']),
            $product_id,
            $start_date,
            $end_date,
            $total_days,
            $daily_rate,
            (int) ($product['discount_percentage'] ?? 0),
            $delivery_method,
            $delivery_fee,
            $total_price,
            $status,
            $metadata['approved_at'],
            $metadata['completed_at'],
            $metadata['cancelled_at'],
            $metadata['cancel_reason'],
            $rental_code,
        ]
    );
}

function delete_rental_record($rental_code)
{
    if (!db_ready()) {
        return false;
    }

    $rental = find_rental_by_code($rental_code);
    if (!$rental) {
        return false;
    }

    return db_execute('DELETE FROM rentals WHERE rental_code = ?', [$rental_code]);
}

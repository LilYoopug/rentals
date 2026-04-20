<?php

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../returns-data.php';

function get_staff_borrowing_rows()
{
    if (!db_ready()) {
        return [];
    }

    return db_all(
        'SELECT r.*, u.fullname, p.name AS product_name, p.brand, p.category_slug, p.image_path
         FROM rentals r
         JOIN users u ON u.id = r.user_id
         JOIN products p ON p.id = r.product_id
         ORDER BY
             CASE
                 WHEN r.status IN ("menunggu", "mendatang", "pending", "upcoming") THEN 0
                 WHEN r.status IN ("disetujui", "approved") THEN 1
                 WHEN r.status IN ("aktif", "active") THEN 2
                 WHEN r.status IN ("selesai", "completed") THEN 3
                 ELSE 4
             END ASC,
             r.created_at ASC,
             r.id ASC'
    );
}

function get_staff_return_rows()
{
    return get_return_tracking_rows();
}

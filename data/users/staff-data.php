<?php

require_once __DIR__ . '/../../includes/functions.php';

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
         WHERE r.status IN ("pending", "upcoming", "active")
         ORDER BY r.created_at DESC'
    );
}

function get_staff_return_rows()
{
    if (!db_ready()) {
        return [];
    }

    return db_all(
        'SELECT rt.*, r.rental_code, u.fullname, p.name AS product_name, p.brand, p.category_slug, p.image_path
         FROM returns rt
         JOIN rentals r ON r.id = rt.rental_id
         JOIN users u ON u.id = r.user_id
         JOIN products p ON p.id = r.product_id
         ORDER BY rt.created_at DESC'
    );
}

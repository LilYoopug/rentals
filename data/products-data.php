<?php

require_once __DIR__ . '/categories-data.php';

function normalize_product_row($row)
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'brand' => (string) ($row['brand'] ?? ''),
        'category' => normalize_category_slug_value((string) ($row['category_slug'] ?? '')),
        'price' => (float) ($row['price_per_day'] ?? 0),
        'discount' => (int) ($row['discount_percentage'] ?? 0),
        'description' => (string) ($row['description'] ?? ''),
        'image' => public_media_path((string) ($row['image_path'] ?? 'images/gear-placeholder.svg')),
        'inStock' => (bool) ($row['in_stock'] ?? 0),
        'stock' => (int) ($row['stock_available'] ?? 0),
    ];
}

function get_all_products()
{
    if (!db_ready()) {
        return [];
    }

    return array_map('normalize_product_row', db_all('SELECT * FROM products WHERE status IN ("aktif", "active") ORDER BY id ASC'));
}

function get_product_row($id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM products WHERE id = ?', [(int) $id]);
}

function get_product_by_id($id)
{
    $row = get_product_row($id);

    return $row ? normalize_product_row($row) : null;
}

function get_related_products($id, $category_slug)
{
    if (!db_ready()) {
        return [];
    }

    $normalized_slug = normalize_category_slug_value($category_slug);
    $legacy_slug_map = [
        'kamera-mirrorless' => 'mirrorless',
        'lensa' => 'lens',
        'video' => 'video',
    ];
    $legacy_slug = $legacy_slug_map[$normalized_slug] ?? $normalized_slug;

    return array_map(
        'normalize_product_row',
        db_all(
            'SELECT * FROM products
             WHERE id <> ?
               AND category_slug IN (?, ?)
               AND status IN ("aktif", "active")
             ORDER BY id ASC
             LIMIT 4',
            [(int) $id, $normalized_slug, $legacy_slug]
        )
    );
}

function get_admin_products()
{
    if (!db_ready()) {
        return [];
    }

    return db_all('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id ASC');
}

function create_product_record($data)
{
    if (!db_ready()) {
        return false;
    }

    $category_slug = trim((string) ($data['category_slug'] ?? $data['category'] ?? 'kamera-mirrorless'));
    $category = find_category_by_slug($category_slug);
    $category_id = (int) ($data['category_id'] ?? ($category['id'] ?? 1));

    return db_execute(
        'INSERT INTO products (category_id, name, brand, category_slug, price_per_day, discount_percentage, description, image_path, stock_total, stock_available, in_stock, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            $category_id,
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['brand'] ?? '')),
            $category_slug,
            (float) ($data['price_per_day'] ?? $data['price'] ?? 0),
            (int) ($data['discount_percentage'] ?? $data['discount'] ?? 0),
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['image_path'] ?? 'images/gear-placeholder.svg')),
            (int) ($data['stock_total'] ?? $data['total_stock'] ?? 1),
            (int) ($data['stock_available'] ?? $data['stock'] ?? 1),
            (int) ((int) ($data['stock_available'] ?? $data['stock'] ?? 1) > 0),
            trim((string) ($data['status'] ?? 'aktif')),
        ]
    );
}

function update_product_record($id, $data)
{
    if (!db_ready()) {
        return false;
    }

    $category_slug = trim((string) ($data['category_slug'] ?? $data['category'] ?? 'kamera-mirrorless'));
    $category = find_category_by_slug($category_slug);
    $category_id = (int) ($data['category_id'] ?? ($category['id'] ?? 1));
    $stock_available = (int) ($data['stock_available'] ?? $data['stock'] ?? 1);

    return db_execute(
        'UPDATE products SET category_id = ?, name = ?, brand = ?, category_slug = ?, price_per_day = ?, discount_percentage = ?, description = ?, image_path = ?, stock_total = ?, stock_available = ?, in_stock = ?, status = ? WHERE id = ?',
        [
            $category_id,
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['brand'] ?? '')),
            $category_slug,
            (float) ($data['price_per_day'] ?? $data['price'] ?? 0),
            (int) ($data['discount_percentage'] ?? $data['discount'] ?? 0),
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['image_path'] ?? 'images/gear-placeholder.svg')),
            (int) ($data['stock_total'] ?? $data['total_stock'] ?? 1),
            $stock_available,
            (int) ($stock_available > 0),
            trim((string) ($data['status'] ?? 'aktif')),
            (int) $id,
        ]
    );
}

function update_product_stock_and_price($id, $price_per_day, $discount_percentage, $stock_total)
{
    if (!db_ready()) {
        return false;
    }

    $product_id = (int) $id;
    if ($product_id <= 0) {
        return false;
    }

    $product = get_product_row($product_id);
    if (!$product) {
        return false;
    }

    $price = (float) $price_per_day;
    $discount = (int) $discount_percentage;
    $total = (int) $stock_total;
    $reserved_units = max(0, (int) ($product['stock_total'] ?? 0) - (int) ($product['stock_available'] ?? 0));
    $available = max(0, $total - $reserved_units);

    if ($price < 0 || $discount < 0 || $discount > 100 || $total < 0) {
        return false;
    }

    return db_execute(
        'UPDATE products SET price_per_day = ?, discount_percentage = ?, stock_total = ?, stock_available = ?, in_stock = ? WHERE id = ?',
        [$price, $discount, $total, $available, (int) ($available > 0), $product_id]
    );
}

function delete_product_record($id)
{
    if (!db_ready()) {
        return false;
    }

    $product_id = (int) $id;
    
    // Check if product is being used in active rentals
    $active_rentals = db_one(
        'SELECT COUNT(*) as count FROM rentals WHERE product_id = ? AND status IN ("menunggu", "disetujui", "mendatang", "aktif")',
        [$product_id]
    );
    
    if ($active_rentals && (int) $active_rentals['count'] > 0) {
        return false; // Cannot delete product with active rentals
    }
    
    // Check if product is being used in pending returns
    $pending_returns = db_one(
        'SELECT COUNT(*) as count FROM returns r 
         INNER JOIN rentals rt ON r.rental_id = rt.id 
         WHERE rt.product_id = ? AND r.status = "menunggu"',
        [$product_id]
    );
    
    if ($pending_returns && (int) $pending_returns['count'] > 0) {
        return false; // Cannot delete product with pending returns
    }

    return db_execute('DELETE FROM products WHERE id = ?', [$product_id]);
}

function update_product_stock_after_rental($product_id, $delta)
{
    $product = get_product_row($product_id);
    if (!$product) {
        return false;
    }

    $new_stock = max(0, min((int) $product['stock_total'], (int) $product['stock_available'] + (int) $delta));

    return db_execute(
        'UPDATE products SET stock_available = ?, in_stock = ? WHERE id = ?',
        [$new_stock, (int) ($new_stock > 0), (int) $product_id]
    );
}

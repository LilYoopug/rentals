<?php

require_once __DIR__ . '/../includes/functions.php';

function get_all_categories()
{
    if (!db_ready()) {
        return [];
    }

    return db_all('SELECT * FROM categories ORDER BY name ASC');
}

function find_category_by_id($id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM categories WHERE id = ?', [(int) $id]);
}

function find_category_by_slug($slug)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM categories WHERE slug = ?', [$slug]);
}

function create_category($data)
{
    if (!db_ready()) {
        return false;
    }

    $name = trim((string) ($data['name'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? $name));
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $slug));
    $slug = trim($slug, '-');

    return db_execute(
        'INSERT INTO categories (name, slug, description, icon, color, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [
            $name,
            $slug,
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['icon'] ?? 'camera')),
            trim((string) ($data['color'] ?? 'blue')),
            trim((string) ($data['status'] ?? 'aktif')),
        ]
    );
}

function update_category_record($id, $data)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute(
        'UPDATE categories SET name = ?, description = ?, icon = ?, color = ?, status = ? WHERE id = ?',
        [
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['icon'] ?? 'camera')),
            trim((string) ($data['color'] ?? 'blue')),
            trim((string) ($data['status'] ?? 'aktif')),
            (int) $id,
        ]
    );
}

function delete_category_record($id)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute('DELETE FROM categories WHERE id = ?', [(int) $id]);
}

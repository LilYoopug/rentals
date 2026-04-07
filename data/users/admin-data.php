<?php

require_once __DIR__ . '/../../includes/functions.php';

function get_admin_users()
{
    if (!db_ready()) {
        return [];
    }

    return db_all('SELECT * FROM users ORDER BY id ASC');
}

function find_user_record_by_fullname($fullname)
{
    if (!db_ready()) {
        return null;
    }

    return db_one(
        'SELECT * FROM users WHERE fullname = ? ORDER BY id ASC LIMIT 1',
        [trim((string) $fullname)]
    );
}

function create_user_record($data)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute(
        'INSERT INTO users (fullname, email, username, password, role, status, avatar_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            trim((string) ($data['fullname'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['username'] ?? '')),
            (string) ($data['password'] ?? password_hash('user123', PASSWORD_DEFAULT)),
            trim((string) ($data['role'] ?? 'user')),
            trim((string) ($data['status'] ?? 'active')),
            trim((string) ($data['avatar_path'] ?? '')),
        ]
    );
}

function update_user_record($id, $data)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute(
        'UPDATE users SET fullname = ?, email = ?, username = ?, role = ?, status = ?, avatar_path = ? WHERE id = ?',
        [
            trim((string) ($data['fullname'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['username'] ?? '')),
            trim((string) ($data['role'] ?? 'user')),
            trim((string) ($data['status'] ?? 'active')),
            trim((string) ($data['avatar_path'] ?? '')),
            (int) $id,
        ]
    );
}

function delete_user_record($id)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute('DELETE FROM users WHERE id = ?', [(int) $id]);
}

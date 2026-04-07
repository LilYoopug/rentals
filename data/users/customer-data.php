<?php

require_once __DIR__ . '/../../includes/functions.php';

function find_user_by_login($login)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1', [$login, $login]);
}

function find_user_by_email($email)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
}

function find_user_by_id($id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $id]);
}

function create_customer_user($data)
{
    if (!db_ready()) {
        return false;
    }

    $saved = db_execute(
        'INSERT INTO users (fullname, email, username, password, role, status, created_at) VALUES (?, ?, ?, ?, "user", "pending", NOW())',
        [
            trim((string) ($data['fullname'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['username'] ?? '')),
            password_hash((string) ($data['password'] ?? ''), PASSWORD_DEFAULT),
        ]
    );

    if ($saved) {
        db_execute('INSERT INTO user_settings (user_id, language, timezone, theme, is_profile_public, allow_marketing, allow_data_export, updated_at) VALUES (?, "id", "Asia/Jakarta", "dark", 0, 0, 1, NOW())', [db_insert_id()]);
    }

    return $saved;
}

function update_customer_profile($id, $data)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute(
        'UPDATE users SET fullname = ?, email = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, province = ?, zip_code = ?, country = ?, bio = ?, avatar_path = ? WHERE id = ?',
        [
            trim((string) ($data['first_name'] ?? '') . ' ' . (string) ($data['last_name'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['phone'] ?? '')),
            trim((string) ($data['address_line1'] ?? '')),
            trim((string) ($data['address_line2'] ?? '')),
            trim((string) ($data['city'] ?? '')),
            trim((string) ($data['province'] ?? '')),
            trim((string) ($data['zip_code'] ?? '')),
            trim((string) ($data['country'] ?? 'ID')),
            trim((string) ($data['bio'] ?? '')),
            trim((string) ($data['avatar_path'] ?? '')),
            (int) $id,
        ]
    );
}

function update_customer_password($id, $hashed_password)
{
    if (!db_ready()) {
        return false;
    }

    return db_execute('UPDATE users SET password = ? WHERE id = ?', [$hashed_password, (int) $id]);
}

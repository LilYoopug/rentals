<?php

require_once __DIR__ . '/../../includes/functions.php';

function find_user_by_login($login)
{
    if (!db_ready()) {
        return null;
    }

    $normalized_login = normalize_login_identifier($login);

    return db_one(
        'SELECT * FROM users WHERE username IN (?, ?) OR email IN (?, ?) LIMIT 1',
        [$login, $normalized_login, $login, $normalized_login]
    );
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

function build_session_user_payload($user)
{
    if (!is_array($user)) {
        return null;
    }

    // Parse billing_info JSON
    $billing_info = [];
    if (!empty($user['billing_info'])) {
        $decoded = json_decode((string) $user['billing_info'], true);
        if (is_array($decoded)) {
            $billing_info = $decoded;
        }
    }

    return [
        'id' => (int) ($user['id'] ?? 0),
        'fullname' => (string) ($user['fullname'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'username' => (string) ($user['username'] ?? ''),
        'role' => normalize_role_value((string) ($user['role'] ?? 'pelanggan')),
        'avatar_path' => (string) ($user['avatar_path'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'billing_info' => $billing_info,
        // Legacy fields for backward compatibility
        'address_line1' => (string) ($billing_info['address_line1'] ?? ''),
        'address_line2' => (string) ($billing_info['address_line2'] ?? ''),
        'city' => (string) ($billing_info['city'] ?? ''),
        'province' => (string) ($billing_info['province'] ?? ''),
        'zip_code' => (string) ($billing_info['zip_code'] ?? ''),
        'country' => (string) ($billing_info['country'] ?? ''),
        'bio' => (string) ($billing_info['bio'] ?? ''),
    ];
}

function create_customer_user($data)
{
    if (!db_ready()) {
        return false;
    }

    $saved = db_execute(
        'INSERT INTO users (fullname, email, username, password, role, created_at) VALUES (?, ?, ?, ?, "pelanggan", NOW())',
        [
            trim((string) ($data['fullname'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['username'] ?? '')),
            password_hash((string) ($data['password'] ?? ''), PASSWORD_DEFAULT),
        ]
    );

    // User settings are now stored in browser localStorage, not database

    return $saved;
}

function update_customer_profile($id, $data)
{
    if (!db_ready()) {
        return false;
    }

    // Build billing_info JSON
    $billing_info = [
        'address_line1' => trim((string) ($data['address_line1'] ?? '')),
        'address_line2' => trim((string) ($data['address_line2'] ?? '')),
        'city' => trim((string) ($data['city'] ?? '')),
        'province' => trim((string) ($data['province'] ?? '')),
        'zip_code' => trim((string) ($data['zip_code'] ?? '')),
        'country' => trim((string) ($data['country'] ?? 'ID')),
        'bio' => trim((string) ($data['bio'] ?? '')),
    ];

    return db_execute(
        'UPDATE users SET fullname = ?, email = ?, phone = ?, billing_info = ?, avatar_path = ? WHERE id = ?',
        [
            trim((string) ($data['first_name'] ?? '') . ' ' . (string) ($data['last_name'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['phone'] ?? '')),
            json_encode($billing_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

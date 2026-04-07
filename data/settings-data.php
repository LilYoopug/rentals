<?php

require_once __DIR__ . '/users/customer-data.php';

function get_user_settings_row($user_id)
{
    if (!db_ready()) {
        return null;
    }

    return db_one('SELECT * FROM user_settings WHERE user_id = ? LIMIT 1', [(int) $user_id]);
}

function save_user_settings($user_id, $data)
{
    if (!db_ready()) {
        return false;
    }

    $row = get_user_settings_row($user_id);

    if ($row) {
        return db_execute(
            'UPDATE user_settings SET language = ?, timezone = ?, theme = ?, is_profile_public = ?, allow_marketing = ?, allow_data_export = ?, updated_at = NOW() WHERE user_id = ?',
            [
                trim((string) ($data['language'] ?? 'id')),
                trim((string) ($data['timezone'] ?? 'Asia/Jakarta')),
                trim((string) ($data['theme'] ?? 'dark')),
                !empty($data['is_profile_public']) ? 1 : 0,
                !empty($data['allow_marketing']) ? 1 : 0,
                !empty($data['allow_data_export']) ? 1 : 0,
                (int) $user_id,
            ]
        );
    }

    return db_execute(
        'INSERT INTO user_settings (user_id, language, timezone, theme, is_profile_public, allow_marketing, allow_data_export, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            (int) $user_id,
            trim((string) ($data['language'] ?? 'id')),
            trim((string) ($data['timezone'] ?? 'Asia/Jakarta')),
            trim((string) ($data['theme'] ?? 'dark')),
            !empty($data['is_profile_public']) ? 1 : 0,
            !empty($data['allow_marketing']) ? 1 : 0,
            !empty($data['allow_data_export']) ? 1 : 0,
        ]
    );
}

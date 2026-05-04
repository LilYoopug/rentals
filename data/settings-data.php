<?php

require_once __DIR__ . '/users/customer-data.php';

// User settings are now stored in browser localStorage
// These functions return default values for backward compatibility

function get_user_settings_row($user_id)
{
    // Return default settings - actual settings are in browser localStorage
    // Theme is always 'dark' (light mode removed)
    return [
        'user_id' => (int) $user_id,
        'language' => 'id',
        'timezone' => 'Asia/Jakarta',
        'theme' => 'dark', // Always dark mode
        'is_profile_public' => 0,
        'allow_marketing' => 0,
        'allow_data_export' => 1,
        'updated_at' => date('Y-m-d H:i:s'),
    ];
}

function save_user_settings($user_id, $data)
{
    // Settings are saved in browser localStorage, not database
    // Return true for backward compatibility
    return true;
}

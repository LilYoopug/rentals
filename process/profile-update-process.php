<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/users/customer-data.php';
require_once __DIR__ . '/../data/activity-data.php';
require_once __DIR__ . '/../data/settings-data.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_request()) {
    redirect_to('user/profile.php');
}

$user_id = (int) current_user()['id'];

if (!empty($_POST['settings_only'])) {
    save_user_settings($user_id, [
        'language' => $_POST['language'] ?? 'id',
        'timezone' => $_POST['timezone'] ?? 'Asia/Jakarta',
        'theme' => $_POST['theme'] ?? 'dark',
        'is_profile_public' => $_POST['is_profile_public'] ?? 0,
        'allow_marketing' => $_POST['allow_marketing'] ?? 0,
        'allow_data_export' => $_POST['allow_data_export'] ?? 1,
    ]);

    if (!empty($_POST['export_data'])) {
        $user = find_user_by_id($user_id);
        $payload = [
            'generated_at' => gmdate('c'),
            'profile' => $user,
            'settings' => get_user_settings_row($user_id),
            'rentals' => get_customer_rentals($user_id),
            'returns' => get_customer_returns($user_id),
        ];
        add_activity_log($user_id, (string) current_user()['fullname'], (string) current_user()['role'], 'data', 'Meminta ekspor data pribadi.');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lenscraft-data-export-' . $user_id . '.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        add_activity_log($user_id, (string) current_user()['fullname'], (string) current_user()['role'], 'settings', 'Memperbarui preferensi aplikasi.');
        set_flash('success', 'Preferensi berhasil disimpan.');
    }

    $settings_redirect = 'user/appearance.php';
    if (!empty($_POST['export_data']) || array_key_exists('is_profile_public', $_POST) || array_key_exists('allow_marketing', $_POST) || array_key_exists('allow_data_export', $_POST)) {
        $settings_redirect = 'user/privacy.php';
    }

    redirect_to($settings_redirect);
}

$_POST['avatar_path'] = save_uploaded_user_avatar('avatar_file', (string) ($_POST['existing_avatar_path'] ?? ((string) (current_user()['avatar_path'] ?? ''))));

if (update_customer_profile($user_id, $_POST)) {
    $_SESSION['current_user']['fullname'] = trim((string) ($_POST['first_name'] ?? '') . ' ' . (string) ($_POST['last_name'] ?? ''));
    $_SESSION['current_user']['email'] = trim((string) ($_POST['email'] ?? ''));
    $_SESSION['current_user']['avatar_path'] = trim((string) ($_POST['avatar_path'] ?? ''));
    add_activity_log($user_id, (string) $_SESSION['current_user']['fullname'], (string) current_user()['role'], 'profile', 'Memperbarui profil.');
    set_flash('success', 'Profil berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui profil.');
}

redirect_to('user/profile.php');

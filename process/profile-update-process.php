<?php

require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/users/customer-data.php';
require_once __DIR__ . '/../data/activity-data.php';
require_once __DIR__ . '/../data/settings-data.php';
require_once __DIR__ . '/../data/returns-data.php';
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('user/profile.php');
}

$user_id = (int) current_user()['id'];

if (!empty($_POST['settings_only'])) {
    // Settings are now stored in browser localStorage, not database
    // This endpoint is kept for backward compatibility but does nothing
    
    if (!empty($_POST['export_data'])) {
        $user = find_user_by_id($user_id);
        $payload = [
            'generated_at' => gmdate('c'),
            'profile' => $user,
            'settings' => get_user_settings_row($user_id), // Returns default values
            'rentals' => get_customer_rentals($user_id),
            'returns' => get_customer_returns($user_id),
        ];
        add_activity_log($user_id, (string) current_user()['fullname'], (string) current_user()['role'], 'data', 'Meminta ekspor data pribadi.');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lenscraft-data-export-' . $user_id . '.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // Settings saved in localStorage on client side
        add_activity_log($user_id, (string) current_user()['fullname'], (string) current_user()['role'], 'settings', 'Memperbarui preferensi aplikasi (localStorage).');
        set_flash('success', 'Preferensi berhasil disimpan di browser Anda.');
    }

    redirect_to('user/profile.php');
}

$_POST['avatar_path'] = save_uploaded_user_avatar('avatar_file', (string) ($_POST['existing_avatar_path'] ?? ((string) (current_user()['avatar_path'] ?? ''))));

if (update_customer_profile($user_id, $_POST)) {
    $updated_user = find_user_by_id($user_id);
    if ($updated_user) {
        $_SESSION['current_user'] = build_session_user_payload($updated_user);
    }
    add_activity_log($user_id, (string) current_user()['fullname'], (string) current_user()['role'], 'profile', 'Memperbarui profil.');
    set_flash('success', 'Profil berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal memperbarui profil.');
}

redirect_to('user/profile.php');

<?php

require_once __DIR__ . '/../includes/functions.php';

function activity_log_storage_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    if (!db_ready()) {
        $config = null;
        return $config;
    }

    $candidates = [
        ['table' => 'activity_logs', 'time_column' => 'created_at'],
        ['table' => 'activity_log', 'time_column' => 'timestamp'],
    ];

    foreach ($candidates as $candidate) {
        $row = db_one(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$candidate['table']]
        );
        if ($row !== null) {
            $config = $candidate;
            return $config;
        }
    }

    $config = null;
    return $config;
}

function get_activity_logs()
{
    $config = activity_log_storage_config();
    if ($config === null) {
        return [];
    }

    return db_all(
        'SELECT id, user_id, actor_name, actor_role, activity_type, message, ' . $config['time_column'] . ' AS created_at FROM ' . $config['table'] . ' ORDER BY ' . $config['time_column'] . ' DESC, id DESC'
    );
}

function add_activity_log($user_id, $actor_name, $actor_role, $activity_type, $message)
{
    $config = activity_log_storage_config();
    if ($config === null) {
        return false;
    }

    return db_execute(
        'INSERT INTO ' . $config['table'] . ' (user_id, actor_name, actor_role, activity_type, message, ' . $config['time_column'] . ') VALUES (?, ?, ?, ?, ?, NOW())',
        [$user_id, $actor_name, $actor_role, $activity_type, $message]
    );
}

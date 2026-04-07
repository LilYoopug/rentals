<?php

if (!isset($base_url)) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $script_name = str_replace('\\', '/', $script_name);
    $base_dir = str_replace('\\', '/', dirname($script_name));

    foreach (['/process/', '/config/', '/includes/', '/data/', '/database/', '/assets/'] as $marker) {
        $position = strpos($script_name, $marker);
        if ($position !== false) {
            $base_dir = substr($script_name, 0, $position);
            break;
        }
    }

    $base_dir = $base_dir === '/' ? '' : rtrim($base_dir, '/');
    $base_url = $base_dir;
}

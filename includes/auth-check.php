<?php

require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    set_flash('error', 'Silakan masuk terlebih dahulu.');
    redirect_root_to('login.php');
}

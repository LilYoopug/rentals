<?php

require_once __DIR__ . '/../includes/flash.php';

if (is_logged_in()) {
    redirect_to('user/profile.php');
}

set_flash('error', 'Reset kata sandi hanya tersedia dari halaman keamanan setelah login.');
redirect_to('login.php');

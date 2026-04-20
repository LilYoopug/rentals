<?php

require_once __DIR__ . '/auth-check.php';

if (!is_admin_user()) {
    set_flash('error', 'Halaman ini hanya untuk admin.');
    redirect_root_to('products.php');
}

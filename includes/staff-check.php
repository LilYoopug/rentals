<?php

require_once __DIR__ . '/auth-check.php';

if (!is_staff_user()) {
    set_flash('error', 'Halaman ini hanya untuk petugas.');
    redirect_root_to('products.php');
}

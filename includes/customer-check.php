<?php

require_once __DIR__ . '/auth-check.php';

if (!is_customer_user()) {
    set_flash('error', 'Halaman ini hanya untuk customer.');
    redirect_root_to('products.php');
}

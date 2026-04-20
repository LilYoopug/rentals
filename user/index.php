<?php

require_once __DIR__ . '/../includes/flash.php';

$root_base_url = rtrim((string) preg_replace('#/user$#', '', $base_url ?? ''), '/');
header('Location: ' . ($root_base_url === '' ? '' : $root_base_url) . '/products.php');
exit;

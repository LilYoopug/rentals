<?php

require_once __DIR__ . '/../includes/flash.php';

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = root_base_url_path('product-detail.php');
if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target);
exit;

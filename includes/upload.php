<?php

function save_uploaded_product_image($field_name, $existing_path = '')
{
    if (empty($_FILES[$field_name]) || !is_array($_FILES[$field_name])) {
        return $existing_path !== '' ? $existing_path : 'images/gear-placeholder.svg';
    }

    $file = $_FILES[$field_name];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existing_path !== '' ? $existing_path : 'images/gear-placeholder.svg';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existing_path !== '' ? $existing_path : 'images/gear-placeholder.svg';
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowed, true)) {
        return $existing_path !== '' ? $existing_path : 'images/gear-placeholder.svg';
    }

    $upload_dir = __DIR__ . '/../uploads/products';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = 'product-' . date('YmdHis') . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.' . $extension;
    $destination = $upload_dir . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        return $existing_path !== '' ? $existing_path : 'images/gear-placeholder.svg';
    }

    return 'uploads/products/' . $filename;
}

function save_uploaded_user_avatar($field_name, $existing_path = '')
{
    if (empty($_FILES[$field_name]) || !is_array($_FILES[$field_name])) {
        return $existing_path !== '' ? $existing_path : '';
    }

    $file = $_FILES[$field_name];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existing_path !== '' ? $existing_path : '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return $existing_path !== '' ? $existing_path : '';
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowed, true)) {
        return $existing_path !== '' ? $existing_path : '';
    }

    $upload_dir = __DIR__ . '/../uploads/users';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = 'avatar-' . date('YmdHis') . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.' . $extension;
    $destination = $upload_dir . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        return $existing_path !== '' ? $existing_path : '';
    }

    return 'uploads/users/' . $filename;
}

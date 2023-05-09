<?php

$config = include_once __DIR__ . '/config.php';
$endpoint = $config['endpoint'];
$sign = $config['sign'];
if (!isset($_POST['sign']) || !isset($_POST['key'])) {
    exit('param error');
}
$key = $_POST['key'];
if ($sign !== $_POST['sign']) {
    exit('error');
}
$file = $_FILES['file'];
if (!preg_match('#^[a-zA-Z-0-9/\.]+$#', $key)) {
    exit('key error');
}
move_uploaded_file($file['tmp_name'], $key);

echo $endpoint . '/' . $key;

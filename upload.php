<?php

require_once __DIR__ . '/vendor/autoload.php';

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;


$config = require_once __DIR__ . '/config.php';
$accessKey = $config['accessKey'];
$secretKey = $config['secretKey'];
$bucket = $config['bucket'];

$auth = new Auth($accessKey, $secretKey);
$uploadManager = new UploadManager();

$keyPrefix = 'tmp/download/';

$key = $argv[1];
$token = $auth->uploadToken($bucket, $keyPrefix . $key);
list($ret, $err) = $uploadManager->putFile($token, $keyPrefix . $key,  $key);
var_dump($ret, $err);
var_dump('http://image.sourcedev.cc/tmp/download/' . $key);
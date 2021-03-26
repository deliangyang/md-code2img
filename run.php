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

$content = file_get_contents($argv[1]);

preg_match_all('#(```[^\n]+\n)(.+)(```)#sUm', $content, $matches);

preg_match_all('#!\[([^\]]+)\]\(([^\)]+)\)#', $content, $matchImages);

if (3 === count($matchImages)) {
    foreach ($matchImages[2] as $k => $matchImage) {
        if (false !== strpos($matchImage, 'http://')
            || false !== strpos($matchImage, 'https://')) {
            continue;
        }
        $dirname = dirname($matchImage);
        $key = str_replace('/', '', str_replace($dirname, '', $matchImage));
        $filename = dirname($argv[1]) . DIRECTORY_SEPARATOR . $matchImage;

        $token = $auth->uploadToken($bucket, $keyPrefix . $key);
        list($ret, $err) = $uploadManager->putFile($token, $keyPrefix . $key, $filename);

        $content = str_replace(
            $matchImages[0][$k],
            sprintf('![%s](%s)', $key, 'http://image.sourcedev.cc/tmp/download/' . $key),
            $content
        );
    }
}

$noCodeBlockContent = preg_replace('#(```[^\n]+\n)(.+)(```)#sUm', '', $content);

preg_match_all('#`([^`]+)`#', $noCodeBlockContent, $codeMatches);
foreach ($codeMatches[0] as $k => $codeMatch) {
    if (isset($argv[2])) {
        $content = str_replace($codeMatch, '<code style="color:red;">' . $codeMatches[1][$k] .'</code>', $content);
    } else {
        $content = str_replace($codeMatch, $codeMatches[1][$k], $content);
    }
}
// var_dump($matchImages);
//var_dump($matches);
//exit;
foreach ($matches[2] as $k => $match) {
    file_put_contents('/tmp/' . $k . '.txt', $match);
    $filename = $k . '-' . md5($match);


    $key = $filename . '.png';
    if (!file_exists($key)) {
        echo `carbon-now /tmp/$k.txt -t $filename -h`;
        echo `rm -rf /tmp/$k.txt`;
        $token = $auth->uploadToken($bucket, $keyPrefix . $key);
        list($ret, $err) = $uploadManager->putFile($token, $keyPrefix . $key, $key);
    }

    $content = str_replace(
        $matches[1][$k] . $match . $matches[3][$k],
        sprintf('![%s](%s)', $filename, 'http://image.sourcedev.cc/tmp/download/' . $key),
        $content
    );
}

file_put_contents('b.md', $content);

`php a.php b.md > b.html`;


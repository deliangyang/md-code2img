<?php

require_once __DIR__ . '/vendor/autoload.php';

use cebe\markdown\GithubMarkdown;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;


$config = require_once __DIR__ . '/config.php';
$accessKey = $config['accessKey'];
$secretKey = $config['secretKey'];
$bucket = $config['bucket'];
$domain = $config['domain'];

$auth = new Auth($accessKey, $secretKey);
$uploadManager = new UploadManager();

$keyPrefix = 'tmp/download/';

$srcFilename = $argv[1];

$content = file_get_contents($srcFilename);

// 包含时序图
if (preg_match('#```mermaid#', $content)) {
    $dirname = dirname($srcFilename);
    `markdown_mermaid_to_images -m $srcFilename -o $dirname`;

    $content = file_get_contents($srcFilename);
}

preg_match_all('#(```[^\n]+\n)(.+)(```)#sUm', $content, $matches);

preg_match_all('#!\[([^\]]+)\]\(([^\)]+)\)#', $content, $matchImages);

if (3 === count($matchImages)) {
    foreach ($matchImages[2] as $k => $matchImage) {
        if (false !== strpos($matchImage, 'http://')
            || false !== strpos($matchImage, 'https://')) {
            continue;
        }
        $dirname = dirname($matchImage);
        if ($dirname !== '.') {
            $key = $keyPrefix . str_replace('/', '', str_replace($dirname, '', $matchImage));
        } else {
            $key = $keyPrefix . $matchImage;
        }
        $filename = dirname($srcFilename) . DIRECTORY_SEPARATOR . $matchImage;

        $token = $auth->uploadToken($bucket, $key);
        list($ret, $err) = $uploadManager->putFile($token, $key, $filename);

        $content = str_replace(
            $matchImages[0][$k],
            sprintf('![%s](%s)', $key, $domain . $key),
            $content
        );
    }
}

$noCodeBlockContent = preg_replace('#(```[^\n]+\n)(.+)(```)#sUm', '', $content);

preg_match_all('#`([^`]+)`#', $noCodeBlockContent, $codeMatches);
foreach ($codeMatches[0] as $k => $codeMatch) {
    if (isset($argv[2])) {
        $content = str_replace($codeMatch, '<code style="color:red;">' . $codeMatches[1][$k] . '</code>', $content);
    } else {
        $content = str_replace($codeMatch, $codeMatches[1][$k], $content);
    }
}

foreach ($matches[2] as $k => $match) {
    file_put_contents('/tmp/' . $k . '.txt', $match);
    $filename = $k . '-' . md5($match);
    $localFileName = 'image/' . $k . '-' . md5($match);

    $key = $filename . '.png';
    $localFileNameKey = $localFileName . '.png';
    var_dump('current local file: ' . $localFileNameKey);
    if (!file_exists($localFileNameKey)) {
        echo `carbon-now /tmp/$k.txt -t $localFileName -h`;
        echo `rm -rf /tmp/$k.txt`;
        $token = $auth->uploadToken($bucket, $keyPrefix . $key);
        list($ret, $err) = $uploadManager->putFile($token, $keyPrefix . $key, $localFileNameKey);
    }

    $content = str_replace(
        $matches[1][$k] . $match . $matches[3][$k],
        sprintf('![%s](%s)', $filename, $domain . 'tmp/download/' . $key),
        $content
    );
}

$parser = new GithubMarkdown();
$content = $parser->parse($content);
$content = str_replace('(<em>)', '(*)', $content);
$content = str_replace('(</em>)', '(*)', $content);

$html = file_get_contents('extra/template.html');

$html = str_replace('__REPLACE__', $content, $html);

file_put_contents('index.html', $html);

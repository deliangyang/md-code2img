<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<?php

require_once __DIR__ . '/vendor/autoload.php';

class T extends \cebe\markdown\GithubMarkdown
{
    protected function renderImage($block)
    {
        return parent::renderImage($block);
        $image = file_get_contents($block['url']);
        return sprintf(
            '<p><img src="data:image;base64,%s" alt="0-89b7bf630a982cb06c6b59883272f07f.png" /></p>',
            chunk_split(base64_encode($image))
        );
    }
}

$parser = new T();
$content = $parser->parse(file_get_contents($argv[1]));
$content = str_replace('(<em>)', '(*)', $content);
$content = str_replace('(</em>)', '(*)', $content);
echo $content;
?>

</body>
</html>

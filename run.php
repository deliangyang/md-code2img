<?php

require_once __DIR__ . '/vendor/autoload.php';
$config = include_once __DIR__ . '/config.php';

use cebe\markdown\GithubMarkdown;

class Code2Image
{

    protected $filename;

    protected $current;

    private $hasMermaind = false;

    private $originContent;

    private $server = '';

    private $sign = '';

    public function __construct(string $filename)
    {
        global $config;

        $this->sign = $config['sign'];
        $this->server = $config['endpoint'] . '/fileserver.php';
        $this->filename = $filename;
        $this->originContent = file_get_contents($filename);
        $this->current = md5($this->originContent);
        if (preg_match('#```mermaid#', $this->originContent)) {
            $this->hasMermaind = true;
        }
    }

    protected function run()
    {
        if ($this->hasMermaind) {
            $this->mermaind2Image();
        } else {
            file_put_contents($this->mTemp(), $this->originContent);
        }
        $this->c2image();
    }

    protected function mermaind2Image()
    {
        # -t dark -b transparent
        $command = sprintf(
            'mmdc -i %s -o %s -e png',
            $this->filename,
            $this->mTemp()
        );
        $this->log($command);
        return `$command`;
    }

    protected function mTemp(): string
    {
        return sprintf('data/%s.md', $this->current);
    }

    protected function c2image()
    {
        $content = file_get_contents($this->mTemp());
        if (!preg_match_all('#(```[^\n]+\n)(.+)(```)#sUm', $content, $matches)) {
            return;
        }

        foreach ($matches[2] as $k => $match) {
            $tmpFilename = '/tmp/' . $k . '.txt';
            file_put_contents($tmpFilename, $match);
            $filename = $k . '-' . md5($match);
            $localFileName = 'data/' . $k . '-' . md5($match);
            $localFileNameKey = $localFileName . '.png';

            $this->log('current local file: ' . $localFileNameKey);
            if (!file_exists($localFileNameKey)) {
                $command = sprintf('carbon-now %s -t %s -h', $tmpFilename, $localFileName);
                $this->log($command);
                $output = `$command`;
                $this->log(strval($output));
                @unlink($tmpFilename);
            }

            $content = str_replace(
                $matches[1][$k] . $match . $matches[3][$k],
                sprintf('![%s](%s)', $filename, $this->upload($localFileNameKey, $localFileNameKey)),
                $content
            );
        }
        file_put_contents($this->mTemp(), $content);
    }

    private function tobase64(string $filename): string
    {
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        $data = file_get_contents($filename);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    private function log(string $text)
    {
        fwrite(STDERR, date('[Y-m-d H:i:s]') . ' ' . $text . PHP_EOL);
    }

    public function outputHTML(): string
    {
        $this->run();
        $parser = new GithubMarkdown();
        $content = $parser->parse(file_get_contents($this->mTemp()));
        $content = str_replace('(<em>)', '(*)', $content);
        $content = str_replace('(</em>)', '(*)', $content);
        $html = file_get_contents('extra/template.html');
        $html = str_replace('__REPLACE__', $content, $html);
        return $html;
    }

    protected function upload(string $filename, string $key): string
    {
        $command = sprintf(
            "curl -s -XPOST '%s' -F 'sign=%s' -F 'file=@%s' -F 'key=%s'",
            $this->server,
            $this->sign,
            $filename,
            $key
        );
        $this->log($command);
        $output = `$command`;
        return trim($output);
    }
}


$code2Image = new Code2Image($argv[1]);
echo $code2Image->outputHTML();
// $keyPrefix = 'tmp/download/';

// $srcFilename = $argv[1];

// $content = file_get_contents($srcFilename);

// $originContent = $content;
// // 包含时序图
// if (preg_match('#```mermaid#', $content)) {
//     $dirname = dirname($srcFilename);
//     `markdown_mermaid_to_images -m $srcFilename -o $dirname`;

//     $content = file_get_contents($srcFilename);
//     file_put_contents($srcFilename, $originContent);
// }

// preg_match_all('#(```[^\n]+\n)(.+)(```)#sUm', $content, $matches);

// preg_match_all('#!\[([^\]]+)\]\(([^\)]+)\)#', $content, $matchImages);


// $noCodeBlockContent = preg_replace('#(```[^\n]+\n)(.+)(```)#sUm', '', $content);

// preg_match_all('#`([^`]+)`#', $noCodeBlockContent, $codeMatches);
// foreach ($codeMatches[0] as $k => $codeMatch) {
//     if (isset($argv[2])) {
//         $content = str_replace($codeMatch, '<code style="color:red;">' . $codeMatches[1][$k] . '</code>', $content);
//     } else {
//         $content = str_replace($codeMatch, $codeMatches[1][$k], $content);
//     }
// }

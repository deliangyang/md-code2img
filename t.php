<?php

$a = file_get_contents('/tmp/2.txt');
foreach(explode("\r", $a) as $item) {
    $line = trim($item);
    if (empty($line)) {
        continue;
    }
    if (false === strpos($line, 'getInstance')) {
        continue;
    }

    $datum = explode(":", $line);
    $info = trim($datum[1]);
    $method = 'GET';
    if (false !== strpos($info, 'postDataAsyn')) {
        $method = 'POST';
    }

    if (false !== strpos($info, 'R.string.string_url')) {
        preg_match('#\+([^,]+)#', $info, $match);
        $content = file_get_contents('/Users/ydl/Downloads/ddb-tars/ddb/' . $datum[0]);
        preg_match_all('#String url = ([^;]+)#', $content, $matches);
        if ($matches) {

        }
        if ($match) {
            $result = trim(
                    str_replace(' ', '',
                        str_replace('+', '',
                            str_replace('"', '', $match[1])))
            );
        }
    } else {
        continue;
    }

    # var_dump($datum[0], trim($datum[1]));
}
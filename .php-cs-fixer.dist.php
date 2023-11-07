<?php

$file = __DIR__.'/.php-cs-fixer.temp.php';

\file_put_contents(
    $file,
    \file_get_contents('https://raw.githubusercontent.com/zenstruck/.github/main/.php-cs-fixer.dist.php')
);

try {
    return require $file;
} finally {
    \unlink($file);
}

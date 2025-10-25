<?php

$file = __DIR__.'/.php-cs-fixer.temp.php';

\file_put_contents(
    $file,
    \file_get_contents('https://raw.githubusercontent.com/zenstruck/.github/main/.php-cs-fixer.dist.php')
);

/** @var PhpCsFixer\Config $csFixerConfig */
$csFixerConfig = require $file;
$csFixerConfig->setFinder(
    $csFixerConfig->getFinder()
        ->in(__DIR__.'/utils')
        ->in(__DIR__.'/config')
);

try {
    return $csFixerConfig;
} finally {
    \unlink($file);
}

<?php

$target = $argv[1];
$src = file_get_contents($target);

if (!$src) {
    die("Failed to read file '$target'\n");
}

$t = token_get_all($src);
echo count($t) . " tokens, ".strlen($src) . " bytes\n";

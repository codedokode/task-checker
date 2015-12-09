<?php

spl_autoload_register(function ($klass) {
    $root = dirname(__DIR__);
    $path = $root . '/' . strtr($klass, ['\\' => '/']) . '.php';
    
    if (file_exists($path)) {
        require $path;
    }
});

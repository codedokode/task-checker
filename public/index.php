<?php

// Builtin web-server support
if (php_sapi_name() == 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }
}

require __DIR__ . '/../server/server-bootstrap.php';
$app->run();


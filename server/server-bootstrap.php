<?php 

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Файл инициализации серверной части приложения. Создает и
 * заполняет DI контейнер Silex, роуты и обработчик ошибок
 * для веба.
 */

$app = require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/controllers.php';

$app->error(function ($e, Request $request, $code) {
    // $e может быть \Throwable (PHP7) или \Exception (PHP5)
    switch ($code) {
        case 404:
            $message = 'Страница не найдена';
            break;
        default:
            $message = "Произошла ошибка ($code)";
    }

    if ($code != 404) {
        error_log($e->__toString());
    }

    $html = <<<HTML
<meta charset="utf-8">
<p>$message. Вы можете попробовать обновить страницу или 
<a href="/">вернуться на главную страницу</a></p>
HTML;

    if (ini_get('display_errors')) {
        $html .= <<<HTML
<hr>
<pre>
{$e->__toString()}
</pre>
HTML;
    }

    return new Response($html, $code);
});

return $app;


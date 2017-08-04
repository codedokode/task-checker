<?php 

/**
 * Файл инициализации приложения. Создает и заполняет
 * DI контейнер, задает нужные настройки.
 */

use Silex\Provider\CsrfServiceProvider;
use Silex\Provider\TwigServiceProvider;
use TaskChecker\Codebot\EvalClient;
use TaskChecker\ModuleFactory;
use TaskChecker\Reporter\TwigPrinter;
use TaskChecker\TaskListManager;
use TaskChecker\Web\TcUrlGenerator;

require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(-1);

// Fail on any error
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting()) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    return false;
});

$viewsPath = dirname(__DIR__) . '/templates/';
$viewsCachePath = dirname(__DIR__) . '/cache/twig/';

$app = new Silex\Application;
$app->register(new TwigServiceProvider, [
    'twig.path'     =>  $viewsPath,
    'twig.options'  =>  [
        'strict_variables'   => true,
        'cache'              => $viewsCachePath,
        'auto_reload'        => true
    ]
]);

$app['codebot_client'] = function ($c) { 
    return new EvalClient;
};

$app['module_factory'] = function ($c) {
    return new ModuleFactory($c['codebot_client']);
};

$app['task_list_manager'] = function ($c) {
    return new TaskListManager;
};

$app['renderer'] = $app->protect(function ($template, array $args = []) use ($app) {
    // Глобальные переменные, нужные в каждом запросе
    $taskListManager = $app['task_list_manager'];
    $args['gTasks'] = $taskListManager->getTaskList();
    $args['gUrlGenerator'] = $app['tc_url_generator'];
    $twig = $app['twig'];

    return $twig->render($template, $args);
});

$app['twig_printer'] = $app->factory(function ($c) {
    return new TwigPrinter($c['twig']);
});

$app['tc_url_generator'] = function ($c) {
    return new TcUrlGenerator($c['url_generator']);
};


return $app;


<?php 

use TaskChecker\Codebot\EvalClient;
use TaskChecker\ModuleFactory;
use TaskChecker\TaskListManager;

require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(-1);

// Fail on any error
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting()) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    return false;
});

$viewsPath = dirname(__DIR__) . '/server/templates/';

$app = new Silex\Application;
$app->register(new Silex\Provider\TwigServiceProvider, [
    'twig.path' =>  $viewsPath
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
    $args['tasks'] = $taskListManager->getTaskList();

    return $app->render($template, $args);
});

return $app;


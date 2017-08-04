<?php 

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TaskChecker\Web\Csrf\CsrfService;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

// Validate all incoming requests
$csrfService = new CsrfService(
    new UriSafeTokenGenerator,
    true
);

$app->before([$csrfService, 'runBefore']);
$app->after([$csrfService, 'runAfter']);
$app['csrf_service'] = $csrfService;

$app->get('/', function (Application $app) {
    $renderer = $app['renderer'];
    return $renderer('index.html.twig');
})->bind('index');

$app->match('/check/{taskId}', 
    function (Application $app, Request $request, $taskId) use ($csrfService) {
    
    $taskListManager = $app['task_list_manager'];
    $task = $taskListManager->getTaskById($taskId);

    if (!$task) {
        $app->abort(404, "Invalid task id");
    }

    $error = '';
    $source = '';
    $report = null;
    $wasChecked = false;

    if ($request->isMethod('POST')) {
        $source = $request->request->get('source');
        $error = validateCheckRequest($source);

        if (!$error) {
            // Run test
            $test = $taskListManager->createTesterForTask(
                $app['module_factory'],
                $task
            );

            $report = $test->run($source);
            $wasChecked = true;
        }
    }

    $renderer = $app['renderer'];
    $csrfToken = $app['csrf_service']->makeToken();
    $twigPrinter = $app['twig_printer'];

    return $renderer('viewTask.html.twig', [
        'task'      =>  $task,
        'source'    =>  $source,
        'error'     =>  $error,
        'report'    =>  $report,
        'wasChecked'=>  $wasChecked,
        'csrfToken' =>  $csrfToken,
        'twigPrinter'=> $twigPrinter
    ]);

})->method('GET|POST')->bind('viewTask');

function validateCheckRequest($code) {
    $code = trim($code);
    if ($code === '') {
        return "Пожалуйста, введите исходный код программы";
    }

    return null;
}

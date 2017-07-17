<?php 

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$app->get('/', function (Application $app) {
    $renderer = $app['renderer'];
    return $renderer('index.html.twig');
});

$app->match('/check/{taskId}', function (Application $app, Request $request, $taskId) {
    
    $taskListManager = $app['task_list_manager'];
    $task = $taskListManager->getTaskById($taskId);

    if (!$task) {
        $app->abort(404, "Invalid task id");
    }

    $error = '';
    $code = '';
    $report = null;
    $wasChecked = false;

    if ($request->isMethod('POST')) {
        $code = $request->request->get('code');
        $error = validateCheckRequest($code);

        if (!$error) {
            // Run test
            $test = $taskListManager->createTesterForTask(
                $app['module_factory'],
                $task
            );

            $report = $test->run($code);
            $wasChecked = true;
        }
    }

    $renderer = $app['renderer'];
    return $renderer('check.html.twig', [
        'code'      =>  $code,
        'error'     =>  $error,
        'report'    =>  $report,
        'wasChecked'=>  $wasChecked
    ]);

})->method('GET|POST');

function validateCheckRequest($code) {
    $code = trim($code);
    if ($code === '') {
        return "Пожалуйста, введите код программы";
    }

    return null;
}

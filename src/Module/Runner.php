<?php

namespace TaskChecker\Module;

use TaskChecker\Codebot\ClientInterface;
use TaskChecker\Codebot\RunScriptTask;
use TaskChecker\Errors\ExecuteTaskFailedError;
use TaskChecker\Errors\StderrNotEmptyError;
use TaskChecker\Errors\VariableInjectError;
use TaskChecker\Promise\UnfailingPromise;
use TaskChecker\Reporter\Report;
use TaskChecker\RunnerQueuedTask;
use TaskChecker\Step\RunScriptStep;
use TaskChecker\Step\Step;
use TaskChecker\Task;
use TaskChecker\TextScanner\TokenArray;
use TaskChecker\TextScanner\VariableInjector;

/**
 * Contains methods allowing to run a program with provided data 
 * via code execution service.
 *
 * Runner doesn't run the tasks immediately. Instead, it collects
 * the tasks and runs them when runAllTasks() method is called.
 */
class Runner extends BaseModule
{
    private $code;

    /**
     * @var VariableInjector 
     */
    private $injector;

    // *
    //  * @var callable[] Functions to call before test
     
    // private $onExecuteHandlers = [];

    /**
     * @var Codebot 
     */
    private $codebotClient;

    /**
     * @var Report 
     */
    private $reporter;

    /**
     * @var RunnerQueuedTask[]
     */
    private $queuedTasks = [];

    private $allowQueueing = true;

    public function __construct(
        ClientInterface $codebotClient, 
        VariableInjector $injector,
        Report $reporter,
        $code)
    {
        $this->code = $code;
        $this->injector = $injector;
        $this->reporter = $reporter;
        $this->codebotClient = $codebotClient;
    }

    public function disableQueuingNewTasks()
    {
        $this->allowQueueing = false;
    }
    

    // public function onExecute(callable $handler)
    // {
    //     $this->onExecuteHandlers[] = $handler;
    // }

    /** @return UnfailingPromise */
    public function queueRunning(array $variables, callable $verifier)
    {
        if (!$this->allowQueueing) {
            throw new \LogicException("Adding new tasks is alredy disabled");
        }

        $queuedTask = new RunnerQueuedTask($variables, $verifier);
        $this->queuedTasks[] = $queuedTask;
        return $queuedTask->getPromise();
    }
    

    /**
     * @param array $data Contains input values and expected output values. Input
     *                    values keys are labeled with @, for example:
     *                    ['@a' => 1, '@b' => 2, 'c' => 3]
     *
     * @return UnfailingPromise 
     */
    public function queueRunningForArray(array $data, callable $verifier)
    {
        if (!$data) {
            throw new \Exception("No input data given");
        }

        $promises = [];

        foreach ($data as $id => $row) {
            $promises[] = $this->queueRunning($row, $verifier);
        }
        
        return $promises;
    }

    public function runQueuedTasks($code)
    {
        $queuedTasks = $this->queuedTasks;
        $this->disableQueuingNewTasks();

        // $this->queuedTasks = [];

        // Этот шаг может провалиться при ошибке в синтаксисе программы или
        // ошибки подстановки переменных в код
        $this->reporter->check("Подготовка программы к запуску", 
            function ($step) use ($queuedTasks, $code) {
                
                $tasks = $this->prepareQueuedTasksInsideStep($code, $queuedTasks);
                $this->codebotClient->execute($tasks);
        });

        // Проверяем результат выполнения программы
        foreach ($queuedTasks as $queuedTask) {
            $codebotTask = $queuedTask->getCodebotTask();
            assert($codebotTask->isExecuted());

            $inputVariables = $this->getInputVariables($queuedTask->getVariables());
            $step = new RunScriptStep($codebotTask, $inputVariables);

            $this->reporter->executeStep($step, function () use ($queuedTask, $codebotTask) {

                $this->checkSuccessulRun($codebotTask);

                $verifier = $queuedTask->getVerifier();
                $verifier($codebotTask->stdout, $queuedTask->getVariables());

                // Сообщаем об успешном выполнении кода
                $queuedTask->resolvePromise();
            });
        }
    }
    

    /**
     * @return RunScriptTask[]
     */
    private function prepareQueuedTasksInsideStep($programCode, array $queuedTasks)
    {
        $codeTokens = TokenArray::fromCode($programCode);
        $runTasks = [];

        foreach ($queuedTasks as $queuedTask) {
            $inputVariables = $this->getInputVariables($queuedTask->getVariables());
            $errors = [];
            $codeWithVars = $this->injector->inject($codeTokens, $inputVariables, $errors);

            if ($errors) {
                throw new VariableInjectError($programCode, reset($errors));
            }

            $task = new RunScriptTask($codeWithVars);
            $queuedTask->setCodebotTask($task);
            $runTasks[] = $task;
        }

        return $runTasks;
    }
    


    //     $this->reporter->check("Проверка программы", function ($step) use($data, &$tasks) {
    //         $tasks = $this->prepareCodebotTasks($data);
    //         $this->codebotClient->execute($tasks);
    //     });

    //     foreach ($data as $id => $row) {
    //         $task = $tasks[$id];
    //         $inputVariables = $this->getInputVariables($row);

    //         $step = new RunScriptStep($task, $inputVariables);
    //         $this->reporter->executeStep($step, function () use ($task, $verify, $row) {

    //             $this->checkSuccessulRun($task);

    //             foreach ($this->onExecuteHandlers as $handler) {
    //                 $handler($task, $row);
    //             }

    //             $verify($task->stdout, $row);
    //         });
    //     }
    // }

    // private function prepareCodebotTasks(array $data)
    // {
    //     $codeTokens = TokenArray::fromCode($this->code);

    //     foreach ($data as $id => $row) {
    //         $inputVariables = $this->getInputVariables($row);
    //         $errors = [];
    //         $codeWithVars = $this->injector->inject($codeTokens, $inputVariables, $errors);

    //         if ($errors) {
    //             throw new VariableInjectError($this->code, reset($errors));
    //         }

    //         $task = new RunScriptTask($codeWithVars);
    //         $tasks[$id] = $task;
    //     }

    //     return $tasks;
    // }  

    private function getInputVariables(array $data)
    {
        $inputs = [];
        foreach ($data as $name => $value) {
            if (preg_match("/^@/", $name)) {
                $varName = mb_substr($name, 1);
                $inputs[$varName] = $value;
            }
        }

        return $inputs;
    }
    
    private function checkSuccessulRun(RunScriptTask $task)
    {
        if ($task->status != RunScriptTask::STATUS_EXECUTED) 
        {
            throw new ExecuteTaskFailedError($task);
        }

        if ($task->stderr !== '')
        {
            throw new StderrNotEmptyError($task->stderr);
        }
    }    
}
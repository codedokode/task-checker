<?php

namespace TaskChecker\Module;

use TaskChecker\Codebot\ClientInterface;
use TaskChecker\Codebot\RunScriptTask;
use TaskChecker\Errors\ExecuteTaskFailedError;
use TaskChecker\Errors\StderrNotEmptyError;
use TaskChecker\Errors\VariableInjectError;
use TaskChecker\Reporter\Report;
use TaskChecker\Step\RunScriptStep;
use TaskChecker\Step\Step;
use TaskChecker\TextScanner\TokenArray;
use TaskChecker\TextScanner\VariableInjector;

/**
 * Contains methods allowing to run a program with provided data 
 * via code execution service
 */
class Runner extends BaseModule
{
    private $code;

    /**
     * @var VariableInjector 
     */
    private $injector;

    /**
     * @var callable[] Functions to call before test
     */
    private $onExecuteHandlers = [];

    /**
     * @var Codebot 
     */
    private $codebotClient;

    /**
     * @var Report 
     */
    private $reporter;

    private $taskQueue = [];

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

    public function onExecute(callable $handler)
    {
        $this->onExecuteHandlers[] = $handler;
    }

    public function runScript(callable $verify)
    {
        // hack
        $this->runWithVariables([[]], $verify);
    }
    

    /**
     * @param array $data Contains input values and expected output values. Input
     *                    values keys are labeled with @, for example:
     *                    ['@a' => 1, '@b' => 2, 'c' => 3]
     */
    public function runWithVariables(array $data, callable $verify)
    {
        if (!$data) {
            throw new \Exception("No input data given");
        }

        $this->reporter->check("Проверка программы", function ($step) use($data, &$tasks) {
            $tasks = $this->prepareCodebotTasks($data);
            $this->codebotClient->execute($tasks);
        });

        foreach ($data as $id => $row) {
            $task = $tasks[$id];
            $inputVariables = $this->getInputVariables($row);

            $step = new RunScriptStep($task, $inputVariables);
            $this->reporter->executeStep($step, function () use ($task, $verify, $row) {

                $this->checkSuccessulRun($task);

                foreach ($this->onExecuteHandlers as $handler) {
                    $handler($task, $row);
                }

                $verify($task->stdout, $row);
            });
        }
    }

    private function prepareCodebotTasks(array $data)
    {
        $codeTokens = TokenArray::fromCode($this->code);

        foreach ($data as $id => $row) {
            $inputVariables = $this->getInputVariables($row);
            $errors = [];
            $codeWithVars = $this->injector->inject($codeTokens, $inputVariables, $errors);

            if ($errors) {
                throw new VariableInjectError($this->code, reset($errors));
            }

            $task = new RunScriptTask($codeWithVars);
            $tasks[$id] = $task;
        }

        return $tasks;
    }      

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
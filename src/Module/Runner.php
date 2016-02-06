<?php

namespace Module;

use Codebot\ClientInterface;
use Codebot\RunScriptTask;
use Errors\ExecuteTaskFailedError;
use Errors\StderrNotEmptyError;
use Errors\VariableInjectError;
use Reporter\Reporter;
use Reporter\RunScriptStep;
use Reporter\Step;
use TextScanner\TokenArray;
use TextScanner\VariableInjector;

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
     * @var Reporter 
     */
    private $reporter;

    public function __construct(
        ClientInterface $codebotClient, 
        VariableInjector $injector,
        Reporter $reporter,
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
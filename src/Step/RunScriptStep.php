<?php

namespace TaskChecker\Step;

use TaskChecker\Codebot\RunScriptTask;

class RunScriptStep extends Step
{
    private $task;
    private $inputVariables;

    public function __construct(RunScriptTask $task, array $inputVariables)
    {
        parent::__construct("запуск программы");
        $this->task = $task;
        $this->inputVariables = $inputVariables;
    }    

    public function getTask()
    {
        return $this->task;
    }

    public function getInputVariables()
    {
        return $this->inputVariables;
    }
}
<?php

namespace TaskChecker\Step;

use TaskChecker\Codebot\RunScriptTask;
use TaskChecker\Util\StringUtil;

class RunScriptStep extends Step
{
    private $task;
    private $inputVariables;

    public function __construct(RunScriptTask $task, array $inputVariables)
    {
        if (count($inputVariables) > 0) {
            $title = sprintf(
                "запуск программы с подстановкой переменных %s", 
                StringUtil::stringify($inputVariables)
            );
        } else {
            $title = "запуск программы";
        }

        parent::__construct($title);
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
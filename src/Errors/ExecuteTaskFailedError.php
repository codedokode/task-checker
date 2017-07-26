<?php

namespace TaskChecker\Errors;

use TaskChecker\Codebot\RunScriptTask;

class ExecuteTaskFailedError extends BaseTestError
{
    private $task;

    public function __construct(RunScriptTask $task)
    {
        $this->task = $task;
    }

    public function getErrorText()
    {
        return "не удалось запустить программу: {$this->task->failReason}";
    }
}
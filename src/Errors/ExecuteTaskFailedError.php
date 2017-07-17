<?php

namespace TaskChecker\Errors;

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
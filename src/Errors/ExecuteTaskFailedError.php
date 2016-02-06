<?php

namespace Errors;

class ExecuteTaskFailedError extends Error
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
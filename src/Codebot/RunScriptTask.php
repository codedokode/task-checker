<?php

namespace TaskChecker\Codebot;

class RunScriptTask
{
    const STATUS_READY = 1;
    const STATUS_EXECUTED = 2;
    const STATUS_FAILED = 3;

    const FAIL_TIMEOUT = 'timeout';
    const FAIL_STDOUT_LIMIT = 'stdout_limit';
    const FAIL_STDERR_LIMIT = 'stderr_limit';
    const FAIL_MEMORY_LIMIT = 'memory_limit';
    const FAIL_ERROR = 'error';

    public $source;
    public $stdout;
    public $stderr;
    public $status = self::STATUS_READY;
    public $exitCode;
    public $failReason;
    public $timeTaken;
    public $memoryTaken;

    public $stdoutLimit;
    public $stderrLimit;
    public $timeout;
    public $memoryLimit;

    public function __construct($source)
    {
        $this->source = $source;
    }

    public function setFailed($reason)
    {
        $this->status = self::STATUS_FAILED;
        $this->failReason = $reason;
    }

    public function isSuccess()
    {
        return $this->status == self::STATUS_EXECUTED;
    }
    
}
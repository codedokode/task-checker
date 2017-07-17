<?php

namespace TaskChecker\Errors;

class StderrNotEmptyError extends BaseTestError
{
    private $stderr;

    public function __construct($stderr)
    {
        $this->stderr = $stderr;
    }

    public function getErrorText()
    {
        return "список ошибок не пустой";
    }
    
    public function getErrorDescription()
    {
        return "ошибки: {$this->stderr}";
    }
}
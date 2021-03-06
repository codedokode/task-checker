<?php

namespace TaskChecker\Errors;

use TaskChecker\TextScanner\VariableInjectException;

class VariableInjectError extends BaseTestError
{
    private $errorCode;
    private $injectError;

    public function __construct($errorCode, VariableInjectException $injectError)
    {
        parent::__construct("не удалось подставить значения переменных в код", 1, $injectError);
        $this->errorCode = $errorCode;
        $this->injectError = $injectError;
    }

    public function getErrorText()
    {
        return "не удалось подставить в программу переменную: {$this->injectError->getMessage()}";
    }
}
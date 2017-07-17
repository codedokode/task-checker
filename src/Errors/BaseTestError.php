<?php

namespace TaskChecker\Errors;

abstract class BaseTestError extends \Exception
{
    abstract public function getErrortext();

    public function getErrorDescription()
    {
        return null;
    }
}
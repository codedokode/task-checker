<?php

namespace Errors;

abstract class Error extends \Exception
{
    abstract public function getErrortext();

    public function getErrorDescription()
    {
        return null;
    }
}
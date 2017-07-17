<?php

namespace TaskChecker\Errors;

class ReaderNotOneMatchError extends BaseTestError
{
    function __construct($matchCount) 
    {
        parent::__construct("найдено {$matchCount} совпадений вместо одного");
    }

    public function getErrorText()
    {
        return $this->message;
    }
    
}
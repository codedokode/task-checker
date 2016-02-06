<?php

namespace Errors;

class ReaderNotOneMatchError extends Error
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
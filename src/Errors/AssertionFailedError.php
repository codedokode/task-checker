<?php

namespace TaskChecker\Errors;

class AssertionFailedError extends BaseTestError
{
    public function getErrorText()
    {
        if ($this->message) {
            return $this->message;
        }

        return "проверка провалилась";
    }    
}
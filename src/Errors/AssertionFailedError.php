<?php

namespace Errors;

class AssertionFailedError extends Error
{
    public function getErrorText()
    {
        if ($this->message) {
            return $this->message;
        }

        return "проверка провалилась";
    }    
}
<?php

namespace TextScanner;

class VariableInjectException extends \Exception
{
    const ERROR_DECL_NOT_FOUND = 'ERROR_DECL_NOT_FOUND';
    const ERROR_MISSING_SEMICOLON = 'ERROR_MISSING_SEMICOLON';
    const ERROR_VALUE_UNSERIALIZABLE = 'ERROR_VALUE_UNSERIALIZABLE';

    private $varName;
    private $errorCode;

    public function __construct($varName, $errorCode)
    {
        parent::__construct("Cannot inject variable '$varName' because of error $errorCode");
        $this->varName = $varName;
        $this->errorCode = $errorCode;
    }

    public function getVarName()
    {
        return $this->varName;
    }
    
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
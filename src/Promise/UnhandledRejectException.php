<?php 

namespace TaskChecker\Promise;

class UnhandledRejectException extends \Exception
{
    public function __construct($exception)
    {
        parent::__construct(
            "Unhandled rejection in promise: {$exception->getMessage()}",
            0,
            $exception
        );
    }
    
}
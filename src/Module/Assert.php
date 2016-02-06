<?php

namespace Module;

use Errors\AssertionFailedError;
use Reporter\Reporter;
use Reporter\Step;
use Util\String;

class Assert extends BaseModule
{
    private $reporter;

    public function __construct(Reporter $reporter)
    {
        $this->reporter = $reporter;
    }
    
    public function isNumber($value)
    {
        $this->isTrue(
            sprintf("проверим, что %s это число", String::stringify($value)),
            is_numeric($value)
        );
    }

    public function isEqualApproximately($expected, $actual, $precision = 0.03)
    {        
        $allowedError = abs($expected) * $precision;
        $precisionText = $this->formatPrecision($precision, $allowedError);

        $this->isTrue(
            sprintf("проверим что %s равняется %s %s", $actual, $expected, $precisionText),
            abs($actual - $expected) <= $allowedError
        );
    }

    private function formatPrecision($precision, $allowedError)
    {
        if ($precision >= 0.01 && $precision <= 1) {
            return sprintf("± %.1f", $precision * 100);
        }

        return sprintf("± %f", $allowedError);
    }    
     
    public function isTrue($message, $value, $failComment = null)
    {
        return $this->assertThat($message, function () use ($value, $failComment) { 
            if (!$value) {
                throw new AssertionFailedError($failComment);
            }
        });
    }    

    public function assertThat($message, callable $check)
    {
        return $this->reporter->check($message, $check);
    }    
}
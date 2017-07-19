<?php

namespace TaskChecker\Module;

use TaskChecker\Errors\AssertionFailedError;
use TaskChecker\Reporter\Report;
use TaskChecker\Step\Step;
use TaskChecker\Util\StringUtil;

class Assert extends BaseModule
{
    private $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }
    
    public function isNumber($value)
    {
        $this->isTrue(
            sprintf("проверим, что %s это число", StringUtil::stringify($value)),
            is_numeric($value)
        );
    }

    public function isEqualApproximately($expected, $actual, $precision = 0.03)
    {        
        $allowedError = abs($expected) * $precision;
        $precisionText = $this->formatPrecision($precision, $allowedError);

        $this->isTrue(
            sprintf("проверим, что %s равняется %s %s", $actual, $expected, $precisionText),
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

    private function assertThat($message, callable $check)
    {
        return $this->report->check($message, $check);
    }    
}
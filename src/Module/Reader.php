<?php

namespace TaskChecker\Module;

use TaskChecker\Errors\ReaderNotOneMatchError;
use TaskChecker\Reporter\Report;
use TaskChecker\Step\StepWithResult;
use TaskChecker\TextReader\Reader as TextReader_Reader;

class Reader extends BaseModule
{
    private $reporter;

    function __construct(Report $reporter) 
    {
        $this->reporter = $reporter;
    }

    /**
     * Searches and returns exactly one phrase matching given
     * expression
     */
    public function readOne($output, $expression)
    {
        $reader = new TextReader_Reader($expression);
        $varCount = count($reader->getVariableNames());
        if ($varCount != 1) {
            throw new \Exception("Expected to have 1 variable in expression, found $varCount");
        }

        $varNames = $reader->getVariableNames();
        $varName= $varNames[0];
        $result = null;

        $step = new StepWithResult(
            "ищем в выводе программы фразу '{$reader->getPatternText()}'");

        $this->reporter->executeStep(
            $step, 
            function ($step) use($output, $reader, $varName, &$result) {
                $matches = $reader->matchAll($output);

                if (count($matches) != 1) {
                    throw new ReaderNotOneMatchError(count($matches));
                }

                $result = $matches[0][$varName];
                $step->setResult(sprintf('%s = %s', $varName, $result));
        });

        return $result;
    }
}
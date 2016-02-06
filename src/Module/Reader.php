<?php

namespace Module;

use Errors\ReaderNotOneMatchError;
use Reporter\Reporter;
use Util\String;
use textReader\Reader as TextReader_Reader;

class Reader extends BaseModule
{
    private $reporter;

    function __construct(Reporter $reporter) 
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

        $step = $this->reporter->check(
            "ищем в выводе программы фразу '{$reader->getPatternText()}'", 
            function ($step) use($output, $reader, $varName, &$result) {
                $matches = $reader->matchAll($output);

                if (count($matches) != 1) {
                    throw new ReaderNotOneMatchError(count($matches));
                }

                $result = $matches[0][$varName];
        });

        $step->setResult(sprintf('%s = %s', $varName, $result));
        return $result;
    }
}
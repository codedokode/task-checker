<?php

namespace Reporter;

use Reporter\Reporter;
use Reporter\Step;

abstract class Printer
{
    abstract public function printStep(Step $step);
    abstract public function printRunScriptStep(RunScriptStep $step);

    public function printHeader(Reporter $reporter)
    {
        
    }
    
    public function printFooter(Reporter $reporter)
    {
        
    }

    public function printReport(Reporter $reporter)
    {
        $this->printHeader($reporter);
        $this->printSteps($reporter->getSteps());
        $this->printFooter($reporter);
    }

    protected function printSteps(array $steps)
    {
        foreach ($steps as $step) {
            $this->printStepForClass($step);
            if ($step->hasChildren()) {
                $this->printSteps($step->getChildren());
            }
        }
    }
    
    protected function printStepForClass(Step $step)
    {
        switch (get_class($step)) {
            case 'Reporter\\Step':
                $this->printStep($step);
                break;

            case 'Reporter\\RunScriptStep':
                $this->printRunScriptStep($step);
                break;

            default:
                throw new \Exception("Unknown step class " . get_class($step));
        }
    }
}
<?php

namespace TaskChecker\Reporter;

use TaskChecker\Reporter\Report;
use TaskChecker\Step\RunScriptStep;
use TaskChecker\Step\Step;
use TaskChecker\Step\StepWithResult;

abstract class Printer
{
    abstract public function printStep(Step $step);
    abstract public function printStepWithResult(StepWithResult $step);
    abstract public function printRunScriptStep(RunScriptStep $step);

    public function printHeader(Report $reporter)
    {
        
    }
    
    public function printFooter(Report $reporter)
    {
        
    }

    public function printReport(Report $reporter)
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
            case Step::class:
                $this->printStep($step);
                break;

            case RunScriptStep::class:
                $this->printRunScriptStep($step);
                break;

            case StepWithResult::class:
                $this->printStepWithResult($step);
                break;

            default:
                throw new \Exception("Unknown step class " . get_class($step));
        }
    }
}
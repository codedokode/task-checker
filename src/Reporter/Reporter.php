<?php

namespace Reporter;

use Errors\AssertionFailedError;
use Errors\Error;

class Reporter
{
    /**
     * @var Step[] 
     */
    private $steps = [];

    /**
     * @var Step[] 
     */
    private $currentStepsStack = [];

    public function check($comment, callable $action)
    {
        $step = new Step($comment);
        $this->executeStep($step, $action);
        return $step;
    }

    public function executeStep(Step $step, callable $action)
    {
        $this->startStep($step);

        try {
            $action($step);            
        } catch (\Exception $e) {
            $step->setException($e);
            $this->endStep();
            throw $e;
        }

        $this->endStep();
    }

    private function startStep(Step $step)
    {
        if ($this->getCurrenStep()) {
            $this->getCurrenStep()->addChild($step);
        } else {
            $this->steps[] = $step;    
        }

        $this->currentStepsStack[] = $step;
    }

    private function endStep()
    {
        array_pop($this->currentStepsStack);
    }

    protected function getCurrenStep()
    {
        if ($this->currentStepsStack) {
            return end($this->currentStepsStack);
        }

        return null;
    }    

    public function isInsideStep()
    {
        return !!$this->getCurrenStep();
    }
    
    public function getSteps()
    {
        return $this->steps;    
    }
}
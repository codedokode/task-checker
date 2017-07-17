<?php

namespace TaskChecker\Reporter;

use TaskChecker\Errors\AssertionFailedError;
use TaskChecker\Errors\BaseTestError;
use TaskChecker\Errors\Error;
use TaskChecker\Step\Step;

/**
 * Представляет отчет о проверке решения. Отчет состоит из шагов (Step),
 * которые могут быть вложены друг в друга.
 */
class Report
{
    /**
     * @var Step[] 
     */
    private $steps = [];

    /** @var Step */
    private $currentStep;

    public function check($comment, callable $action)
    {
        $step = new Step($comment);
        $this->executeStep($step, $action);
    }

    public function executeStep(Step $step, callable $action)
    {
        $this->startStep($step);

        // TODO: use finally
        // В отчет записываются только исключения, вызванные ошибками
        // при проверке решения 
        try {
            $action($step);
            $step->setSuccess();            
        } catch (BaseTestError $e) {
            $step->setFailed($e);
            $this->endStep();
            throw $e;
        } catch (\Exception $e) {
            // Закрываем шаг, но не записываем исключение в отчет
            $this->endStep();
            throw $e;
        } catch (\Throwable $e) {
            $this->endStep();
            throw $e;
        }

        $this->endStep();
    }

    private function startStep(Step $step)
    {
        assert(!$step->isFinalized());

        if ($this->currentStep) {
            $this->currentStep->addChild($step);
        } else {
            $this->steps[] = $step;
        }

        $this->currentStep = $step;
    }

    private function endStep()
    {
        assert(!!$this->currentStep);
        $this->currentStep = $this->currentStep->getParent();
    }
    
    public function getSteps()
    {
        return $this->steps;    
    }

    /**
     * @return bool 
     */
    public function isSuccessful()
    {
        foreach ($this->steps as $step) {
            if (!$step->isSuccessful()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool 
     */
    public function isFailed()
    {
        foreach ($this->steps as $step) {
            if ($step->isFailed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return BaseTestError|null 
     */
    public function getLastError()
    {
        $steps = $this->steps;
        for ($i = count($steps) - 1; $i >= 0; $i--) { 
            $step = $steps[$i];
            $error = $step->getError();
            if ($error) {
                return $error;
            }
        }

        return null;
    }    
}
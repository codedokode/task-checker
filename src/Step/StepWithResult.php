<?php 

namespace TaskChecker\Step;

/**
 * Шаг, после успешного выполнения которого есть результат. Результат
 * может быть произвольным значением, и должен быть обязательно задан
 * до успешной финализации шага. При ошибке результат может отсутствовать.
 */
class StepWithResult extends Step 
{
    private $hasResult = false;
    private $result;

    public function setResult($result)
    {
        $this->result = $result;
        $this->hasResult = true;
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    public function hasResult()
    {
        return $this->hasResult;
    }
    
    public function setSuccess()
    {
        if (!$this->hasResult) {
            throw new \LogicException(
                "A result must be set before finalizing the step"
            );
        }

        parent::setSuccess();
    }
    
}
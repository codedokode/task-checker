<?php

namespace TaskChecker\Step;

use TaskChecker\Errors\BaseTestError;

/**
 * Представляет собой один шаг в процессе проверки задачи. У шага 
 * обязательно есть описание, результат (успех/ошибка), а также 
 * могут быть дополнительные свойства. 
 *
 * Пример шага: "проверяем, что вывод программы не пуст".
 *
 * Шаги могут быть вложены друг в друга и образовывать дерево.
 */
class Step
{
    private $error;
    private $comment;

    /** @var bool */
    private $success = false;

    /** @var Step[] */
    private $children = [];

    /** @var Step|null */
    private $parent;

    public function __construct($comment)
    {
        assert(is_string($comment));
        $this->comment = $comment;
    }

    /**
     * Помечает шаг как завершенный с ошибкой
     */
    public function setFailed(BaseTestError $error)
    {
        $this->checkIsNotFinalized();
        $this->checkChildrenAreFinalized();
        $this->error = $error;
    }

    /**
     * Помечает шаг как успешно завершенный
     */
    public function setSuccess()
    {
        $this->checkIsNotFinalized();
        $this->checkChildrenAreFinalized();
        $this->success = true;
    }

    public function isFinalized()
    {
        return $this->isSuccessful() || $this->isFailed();
    }

    public function isSuccessful()
    {
        return $this->success;
    }
    
    public function isFailed()
    {
        return !!$this->error;
    }

    public function addChild(Step $step)
    {
        $this->checkIsNotFinalized();
        $step->parent = $this;
        $this->children[] = $step;
    }

    public function getChildren()
    {
        return $this->children;
    }
    
    public function hasChildren()
    {
        return !!$this->children;
    }

    public function getParent()
    {
        return $this->parent;
    }
    
    public function getDepth()
    {
        return $this->parent ? $this->parent->getDepth() + 1 : 0;
    }

    public function isDeepestFailedStep()
    {
        if (!$this->isFailed()) {
            return false;
        }

        foreach ($this->children as $child) {
            if ($child->isFailed()) {
                return false;
            }
        }

        return true;
    }
 
    public function getComment()
    {
        return $this->comment;  
    }
    
    public function getError()
    {
        return $this->error;
    }

    private function checkChildrenAreFinalized()
    {
        foreach ($this->children as $child) {
            if (!$child->isFinalized()) {
                throw new \LogicException(
                    "All child steps must be finalized before finalizing parent step");
            }
        }
    }

    private function checkIsNotFinalized()
    {
        if ($this->isFinalized()) {
            throw new \LogicException("The step is finalized and cannot be modified");
        }
    }
    
}
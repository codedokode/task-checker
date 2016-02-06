<?php

namespace Reporter;

class Step
{
    private $e;
    private $comment;
    private $result;
    private $children = [];
    private $parent;

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    public function setException(\Exception $e)
    {
        $this->e = $e;
    }

    public function addChild(Step $step)
    {
        $step->parent = $this;
        $this->children[] = $step;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }    

    public function getChildren()
    {
        return $this->children;
    }
    
    public function hasChildren()
    {
        return !!$this->children;
    }
    
    public function getDepth()
    {
        return $this->parent ? $this->parent->getDepth() + 1 : 0;
    }

    public function isSuccess()
    {
        return !$this->e;
    }

    public function isDeepestFailedStep()
    {
        if ($this->isSuccess()) {
            return false;
        }

        foreach ($this->children as $child) {
            if (!$child->isSuccess()) {
                return false;
            }
        }

        return true;
    }
 
    public function getComment()
    {
        return $this->comment;  
    }
    
    public function getException()
    {
        return $this->e;
    }

    public function getResult()
    {
        return $this->result;
    }
    
    public function hasResult()
    {
        return $this->result !== null;
    }
    
}
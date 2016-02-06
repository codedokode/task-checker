<?php

namespace Test;

use Reporter\Reporter;

abstract class BaseTest
{
    private $modules = [];

    private $reporter;

    private $solutionCode;

    private $moduleFactory;

    function __construct(\ModuleFactory $moduleFactory) 
    {
        $this->moduleFactory = $moduleFactory;
    }

    abstract protected function runTest();    

    public function run(Reporter $reporter, $solutionCode)
    {
        $this->setReporter($reporter);
        $this->solutionCode = $solutionCode;
        $this->runTest();
        $this->setReporter(null);
        $this->solutionCode = null;
    }

    protected function setReporter(Reporter $reporter = null)
    {
        $this->reporter = $reporter;

        foreach ($this->modules as $module) {
            if (method_exists($module, 'setReporter')) {
                $module->setReporter($reporter);
            }
        }   
    }

    public function __isset($name)
    {
        return isset($this->modules[$name]) || $this->moduleFactory->hasModule($name);
    }

    /**
     * Scenario can access helper modules via properties, e.g. 
     * $this->runner->run(...);
     */
    public function __get($name)
    {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }

        $this->modules[$name] = $this->moduleFactory->getModule($name, $this, $this->reporter);
        return $this->modules[$name];

        // throw new \Exception("No module with name '$name' found");
    }

    public function getSolutionCode()
    {
        return $this->solutionCode;
    }
}

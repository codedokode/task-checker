<?php

namespace TaskChecker\Test;

use TaskChecker\Errors\BaseTestError;
use TaskChecker\ModuleFactory;
use TaskChecker\Reporter\Report;

/**
 * Представляет тест, который можно запустить.
 */
abstract class BaseTest
{
    private $modules = [];

    private $report;

    private $solutionCode;

    private $moduleFactory;

    function __construct(ModuleFactory $moduleFactory) 
    {
        $this->moduleFactory = $moduleFactory;
    }

    abstract protected function runTest();    

    public function run($solutionCode, Report $report = null)
    {
        if (!$report) {
            $report = new Report;
        }

        $this->setReport($report);
        $this->solutionCode = $solutionCode;

        try {
            $this->runTest();

            // Run queued tasks
            $this->runner->runQueuedTasks($solutionCode);

        } catch (BaseTestError $e) {
            // ignore ? 
        }

        $this->setReport(null);
        $this->solutionCode = null;

        return $report;
    }

    protected function setReport(Report $report = null)
    {
        $this->report = $report;
        $this->modules = [];
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

        $this->modules[$name] = $this->moduleFactory->getModule($name, $this, $this->report);
        return $this->modules[$name];

        // throw new \Exception("No module with name '$name' found");
    }

    public function getSolutionCode()
    {
        return $this->solutionCode;
    }
}

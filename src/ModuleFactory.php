<?php

namespace TaskChecker;

use TaskChecker\Codebot\ClientInterface;
use TaskChecker\Reporter\Report;
use TaskChecker\Test\BaseTest;
use TaskChecker\TextScanner\VariableInjector;

class ModuleFactory
{
    private $codebotClient;
    private $injector;

    public function __construct(ClientInterface $codebotClient)
    {
        $this->codebotClient = $codebotClient;
        $this->injector = new VariableInjector;
    }

    public function getModule($name, BaseTest $test, Report $reporter)
    {
        $method = 'getModule' . ucfirst($name);
        return $this->$method($test, $reporter);
    }

    public function hasModule($name)
    {
        $method = 'getModule' . ucfirst($name);
        return method_exists($this, $method);
    }
    
    public function getModuleRunner(BaseTest $test, Report $reporter)
    {
        return new Module\Runner(
            $this->codebotClient, 
            $this->injector, 
            $reporter, 
            $test->getSolutionCode()
        );
    }
    
    public function getModuleReader(BaseTest $test, Report $reporter)
    {
        return new Module\Reader(
            $reporter
        );
    }
    
    public function getModuleUtil()
    {
        return new Module\Util;
    }

    public function getModuleAssert(BaseTest $test, Report $reporter)
    {
        return new Module\Assert($reporter);
    }
    
    
}
<?php

use Codebot\ClientInterface;
use Reporter\Reporter;
use Test\BaseTest;
use TextScanner\VariableInjector;

class ModuleFactory
{
    private $codebotClient;
    private $injector;

    public function __construct(ClientInterface $codebotClient)
    {
        $this->codebotClient = $codebotClient;
        $this->injector = new VariableInjector;
    }

    public function getModule($name, BaseTest $test, Reporter $reporter)
    {
        $method = 'getModule' . ucfirst($name);
        return $this->$method($test, $reporter);
    }

    public function hasModule($name)
    {
        $method = 'getModule' . ucfirst($name);
        return method_exists($this, $method);
    }
    
    public function getModuleRunner(BaseTest $test, Reporter $reporter)
    {
        return new Module\Runner(
            $this->codebotClient, 
            $this->injector, 
            $reporter, 
            $test->getSolutionCode()
        );
    }
    
    public function getModuleReader(BaseTest $test, Reporter $reporter)
    {
        return new Module\Reader(
            $reporter
        );
    }
    
    public function getModuleUtil()
    {
        return new Module\Util;
    }

    public function getModuleAssert(BaseTest $test, Reporter $reporter)
    {
        return new Module\Assert($reporter);
    }
    
    
}
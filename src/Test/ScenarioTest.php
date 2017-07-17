<?php

namespace TaskChecker\Test;

use TaskChecker\ModuleFactory;

/**
 * Test with code storead in a script file
 */
class ScenarioTest extends BaseTest
{
    private $scriptName;

    public function __construct($scriptName, ModuleFactory $moduleFactory)
    {
        if (!file_exists($scriptName)) {
            throw new \InvalidArgumentException("File does not exist: $scriptName");
        }

        parent::__construct($moduleFactory);
        $this->scriptName = $scriptName;
    }
    
    protected function runTest()
    {
        if (!file_exists($this->scriptName)) {
            throw new \Exception("File '{$this->scriptName}' does not exist");
        }

        require $this->scriptName;
    }
    
}
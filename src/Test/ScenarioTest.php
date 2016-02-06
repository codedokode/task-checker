<?php

namespace Test;

use ModuleFactory;

/**
 * Test with code storead in a script file
 */
class ScenarioTest extends BaseTest
{
    private $scriptName;

    public function __construct($scriptName, ModuleFactory $moduleFactory)
    {
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
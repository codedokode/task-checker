<?php 

namespace TaskChecker;

use TaskChecker\Codebot\RunScriptTask;
use TaskChecker\Promise\UnfailingDeferred;
use TaskChecker\Promise\UnfailingPromise;

/**
 * TODO: check if we can merge this with RunScriptStep
 */
class RunnerQueuedTask
{
    /** @var array */
    public $variables;

    /** @var callable */
    private $verifier;

    /** @var RunScriptTask */
    private $task;

    /**
     * @var UnfailingDeferred a deferred that will be resolved into 
     *                      the Task when it is executed
     */
    private $deferred;

    public function __construct(array $variables, callable $verifier)
    {
        $this->deferred = new UnfailingDeferred;
        $this->variables = $variables;
        $this->verifier = $verifier;
    }

    public function setCodebotTask(RunScriptTask $task)
    {
        assert(!$this->task);
        $this->task = $task;
    }

    /** @return RunScriptTask */
    public function getCodebotTask()
    {
        return $this->task;
    }
    
        
    /**
     * @return UnfailingPromise
     */
    public function getPromise()
    {
        return $this->deferred->promise();
    }
    
    public function getVariables()
    {
        return $this->variables;
    }

    /** @return callable */
    public function getVerifier()
    {
        return $this->verifier;
    }
    
    
    public function resolvePromise()
    {
        assert($this->task->isExecuted());
        $this->deferred->resolve($this->task);
    }
}

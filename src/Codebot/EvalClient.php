<?php

namespace TaskChecker\Codebot;

/**
 * For testing purposes mainly
 */
class EvalClient implements ClientInterface
{
    private $outputLimit = INF;
    private $timeLimit = INF;
    private $memoryLimit = INF;

    public function getOutputLimit()
    {
        return $this->outputLimit;
    }
    
    public function setOutputLimit($outputLimit)
    {
        $this->outputLimit = $outputLimit;
    }
    
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }
    
    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }
    
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    public function execute(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->executeTask($task);
        }
    }
    
    private function executeTask(RunScriptTask $task)
    {
        $task->stdout = '';
        $task->stderr = '';

        $e = null;

        try {
            $start = microtime(true);
            $this->evaluateSource($task->source, $task->stdout, $task->stderr);
            $end = microtime(true);
            $task->exitCode = 0;

        } catch (\Exception $e) {
            $end = microtime(true);
            $task->exitCode = 255;
        }

        $task->timeTaken = $end - $start;        
        $task->memoryTaken = 0;
        $task->status = RunScriptTask::STATUS_EXECUTED;

        if ($e) {
            $this->saveException($task, $e);
        }

        $this->checkLimits($task);
    }

    private function checkLimits(RunScriptTask $task)
    {
        $task->stdoutLimit = $this->getOutputLimit();
        $task->stderrLimit = $this->getOutputLimit();
        $task->memoryLimit = $this->getMemoryLimit();
        $task->timeLimit = $this->getTimeLimit();

        if ($task->timeTaken > $task->timeLimit) {
            $task->setFailed(RunScriptTask::FAIL_TIMEOUT);
            return;
        }

        if (strlen($task->stdout) > $task->stdoutLimit) {
            $task->setFailed(RunSciprtTask::FAIL_STDOUT_LIMIT);
            return;
        }

        if (strlen($task->stderr) > $task->stderrLimit) {
            $task->setFailed(RunSciprtTask::FAIL_STDERR_LIMIT);
            return;
        }
    }
    
    private function saveException(RunScriptTask $task, \Exception $e)
    {
        $task->stderr .= $e->__toString() . "\n";
    }

    private function evaluateSource($source, &$stdout, &$stderr)
    {
        $script = '?>' . $source;

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$stderr){
            $type = $this->getErrorType($errno);
            $messsage = "$type: $errstr in file $file, line $line\n";
            $stderr .= $message;
        });

        ob_start();

        try {
            eval($script);
            $stdout = ob_get_clean();
            restore_error_handler();
        } catch (\Exception $e) {
            restore_error_handler();
            $stdout = ob_get_clean();
            throw $e;
        } 
    }       
}
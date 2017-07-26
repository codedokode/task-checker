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

    private $inErrorHandler = false;

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

            // Do not catch errors occured inside error handler
            if ($this->inErrorHandler) {
                throw $e;
            }

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

        $this->inErrorHandler = false;

        set_error_handler(function ($errno, $errstr, $file, $line) use (&$stderr) {

            if ($this->inErrorHandler) {
                throw new \ErrorException($errno, $errstr, $file, $line);
            }

            $this->inErrorHandler = true;
            $type = $this->getErrorType($errno);
            $message = "$type: $errstr in file $file, line $line\n";
            $stderr .= $message;
            $this->inErrorHandler = false;
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

    private function getErrorType($type)
    {
        $types = [
            E_ERROR             => 'PHP Error',
            E_WARNING           => 'PHP Warning',
            E_PARSE             => 'PHP Parse Error',
            E_NOTICE            => 'PHP Notice',
            E_CORE_ERROR        => 'PHP Core Error',
            E_CORE_WARNING      => 'PHP Core Warning',
            E_COMPILE_ERROR     => 'PHP Compile Error',
            E_COMPILE_WARNING   => 'PHP Compile Warning',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'PHP Strict Mode',
            E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
            E_DEPRECATED        => 'PHP Deprecated',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED'
        ];

        return array_key_exists($type, $types) ? $types[$type] : "Error_type_{$type}";
    }         
}
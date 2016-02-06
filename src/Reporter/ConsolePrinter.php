<?php

namespace Reporter;

use Errors\Error;
use Reporter\Reporter;
use Reporter\RunScriptStep;
use Util\String;

class ConsolePrinter extends Printer
{    
    public function printStep(Step $step)
    {
        $this->printStepHeader($step, $step->getComment());
    }

    public function printRunScriptStep(RunScriptStep $step)
    {
        $task = $step->getTask();
        $title = sprintf(
            "запуск программы с переменными %s", 
            String::stringify($step->getInputVariables())
        );

        $this->printStepHeader($step, $title);

        $padding = $this->getStepPadding($step);
        $morePadding = $padding . ' ';

        $code = $this->padText($task->source, $padding . " |");
        $stdout = $this->padText($task->stdout, $padding . " |");

        $this->printLine("{$morePadding}текст программы:\n");
        $this->printLine($code);
        $this->printLine("");

        $this->printLine("{$morePadding}stdout:\n");
        $this->printLine($stdout);
        $this->printLine("");

        if ($task->stderr) {
            $stderr = $this->padText($task->stderr, $padding . " |");
            $this->printLine("{$morePadding}stderr:\n");
            $this->printLine($stderr);
            $this->printLine("");
        }

        if ($task->isSuccess()) {
            $statusText = 'успешно';
        } else {
            $statusText = "ошибка {$task->failReason}";
        }

        $this->printLine(sprintf("%sвремя: %.3f c, %s", $morePadding, $task->timeTaken, $statusText));
        $this->printLine("");
    }

    protected function printStepHeader(Step $step, $title)
    {
        $padding = $this->getStepPadding($step);
        $morePadding = $padding . ' ';

        $result = $step->isSuccess() ? 'ok' : 'fail';
        $this->printLine("{$padding}[$result] $title");

        if ($step->isSuccess() && $step->hasResult()) {
            $this->printLine("{$morePadding}результат: {$step->getResult()}");
        } elseif ($step->isDeepestFailedStep()) {
            $e = $step->getException();

            if ($e instanceof Error) {
                $this->printLine("{$morePadding}ошибка: {$e->getErrorText()}");

                if ($e->getErrorDescription()) {
                    $text = $this->padText($e->getErrorDescription(), $morePadding);
                    $this->printLine("\nПояснение: $text\n");
                }
            } else {
                $text = $this->padText($e->__toString(), $morePadding);
                $this->printLine("{$padding}исключение:\n{$text}");
            }
        }
    }
    
    protected function getStepPadding(Step $step)
    {
        return str_repeat('    ', $step->getDepth());
    }

    protected function padText($text, $padding)
    {
        $lineLength = 80;
        $freeSpace = max(5, $lineLength - mb_strlen($padding));
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            $lineParts = $this->wrapLine($line, $freeSpace);
            foreach ($lineParts as $linePart) {
                $result[] = $padding . $linePart;
            }
        }

        return implode("\n", $result);
    }

    protected function wrapLine($line, $maxLength)
    {
        $parts = [];
        $lineLength = mb_strlen($line);

        for ($i=0; $i < ceil($lineLength / $maxLength); $i++) { 
            $part = mb_substr($line, $i * $maxLength, $maxLength);
            $parts[] = $part;
        }

        return $parts;
    }
 
    protected function printLine($line)
    {
        echo "$line\n";
    }
}
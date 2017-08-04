<?php 

namespace Tests\TaskChecker;

use TaskChecker\Codebot\RunScriptTask;
use TaskChecker\Errors\AssertionFailedError;
use TaskChecker\Reporter\ConsolePrinter;
use TaskChecker\Reporter\Printer;
use TaskChecker\Reporter\Report;
use TaskChecker\Reporter\TwigPrinter;
use TaskChecker\Step\RunScriptStep;
use TaskChecker\Step\Step;
use TaskChecker\Step\StepWithResult;
use Tests\TaskChecker\Helper\TestHelper;

/**
 * Test that report printer can print all kinds of steps
 */
class ReportPrinterTest extends \PHPUnit_Framework_TestCase
{
    public function printersProvider()
    {
        $twig = TestHelper::getService('twig');

        return [
            'ConsolePrinter'    => [new ConsolePrinter()],
            'TwigPrinter'       => [new TwigPrinter($twig)]
        ];
    }

    /**
     * @dataProvider printersProvider
     */
    public function testSimpleStepsArePrinted(Printer $printer)
    {
        $report = new Report;
        $simpleStep = new Step('SimpleStep');
        $report->executeStep($simpleStep, function () use ($report) {
            // Nested step
            $childStep1 = new Step('ChildStep1');
            $report->executeStep($childStep1, function ($step) {});

            $childStep2 = new Step('ChildStep2');
            $report->executeStep($childStep2, function ($step) {});
        });

        $step2 = new Step('SimpleStep2');
        $report->executeStep($step2, function ($step) {});

        $result = $this->printReport($printer, $report);

        $this->assertContains('SimpleStep', $result);
        $this->assertContains('ChildStep1', $result);
        $this->assertContains('ChildStep2', $result);
        $this->assertContains('SimpleStep2', $result);
    }

    /**
     * @dataProvider printersProvider
     */
    public function testStepResultIsPrinted(Printer $printer)
    {
        $report = new Report;

        $stepWithResult = new StepWithResult('StepWithResult');
        $report->executeStep($stepWithResult, function ($step) {
            $step->setResult('TheResultOfStep');
        });

        $result = $this->printReport($printer, $report);

        $this->assertContains('StepWithResult', $result);
        $this->assertContains('TheResultOfStep', $result);
    }
    
    /**
     * @dataProvider printersProvider
     */
    public function testRunScriptReportIsPrinted(Printer $printer)
    {
        $report = new Report;

        $runScriptTask = new RunScriptTask('SourceCodeExample');
        $runScriptTask->stdout = 'stdoutExample';
        $runScriptTask->stderr = 'stderrExample';
        $runScriptTask->status = RunScriptTask::STATUS_EXECUTED;
        $runScriptTask->exitCode = 0;
        $runScriptTask->timeTaken = 1;
        $runScriptTask->memoryTaken = 1000000;

        $runScriptStep = new RunScriptStep(
            $runScriptTask,
            ['exampleVariable' => 1]
        );

        $report->executeStep($runScriptStep, function ($step) {
            // nothing
        });        

        $result = $this->printReport($printer, $report);
        $this->assertContains('stdoutExample', $result);
        $this->assertContains('exampleVariable', $result);
    }
    
    /**
     * @dataProvider printersProvider
     */
    public function testFailedStepIsPrinted(Printer $printer)
    {
        $report = new Report;
        $failedStep = new Step('SimpleFailedStep');

        try {
            $report->executeStep($failedStep, function ($step) {
                throw new AssertionFailedError('FailedStepError');
            });
        } catch (AssertionFailedError $e) {
            // ignore
        }

        $result = $this->printReport($printer, $report);
        $this->assertContains('SimpleFailedStep', $result);
        $this->assertContains('FailedStepError', $result);
    }
    
    private function printReport(Printer $printer, Report $report)
    {
        // Temporary fix for console printer using echo
        ob_start();
        try {
            $result = $printer->printReport($report);
            $output = ob_get_contents();
            $result .= $output;
        } finally {
            ob_end_clean();
        }

        return $result;
    }    
}

<?php 

namespace Tests\TaskChecker;

use TaskChecker\Reporter\ConsolePrinter;
use TaskChecker\Reporter\Report;
use TaskChecker\Step\StepWithResult;
use Tests\TaskChecker\Helper\TestHelper;

class ConsolePrinterTest extends \PHPUnit_Framework_TestCase
{
    public function testPrinterPrints()
    {
        $report = new Report;
        $report->check("Step1", function ($step) use ($report) {
            $report->check("Step11", function ($step) {
                // Nothing
            }); 
        });

        $step2 = new StepWithResult("Step2");
        $report->executeStep($step2, function ($step) {
            $step->setResult("yes");
        });

        $output = TestHelper::captureOutput(function () use ($report) {
            $printer = new ConsolePrinter;
            $printer->printReport($report);
        });

        $this->assertContains('Step1', $output);
        $this->assertContains('Step11', $output);
    }
}

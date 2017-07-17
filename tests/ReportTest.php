<?php

namespace Tests\TaskChecker;

use TaskChecker\Errors\AssertionFailedError;
use TaskChecker\Errors\BaseTestError;
use TaskChecker\Reporter\Report;
use TaskChecker\Step\Step;

class ReportTest extends \PHPUnit_Framework_TestCase
{
    public function testCannotModifyFinalizedStep()
    {
        $step = new Step("Test");
        $step->setSuccess();

        $this->setExpectedException(\LogicException::class);
        $step->setFailed(new AssertionFailedError("Test error"));
    }

    public function testCannotAddChildrenToFinalizedStep()
    {
        $step = new Step("Test");
        $step->setSuccess();

        $child = new Step("Child");

        $this->setExpectedException(\LogicException::class);
        $step->addChild($child);
    }

    public function testStepsCanBeNested()
    {
        $parent = new Step("Parent step");        
        $child1 = new Step("Child 1 step");
        $parent->addChild($child1);
        $child1->setSuccess();

        $child2 = new Step("Child 2 step");
        $parent->addChild($child2);
        $error = new AssertionFailedError("Error example");
        $child2->setFailed($error);

        $parent->setFailed($error);

        $this->assertTrue($parent->isFailed());
        $this->assertFalse($parent->isSuccessful());
        $this->assertCount(2, $parent->getChildren());
        $this->assertNotEmpty($parent->getError());

        $this->assertSame($parent, $child1->getParent());
    }

    public function testSuccessfulReport()
    {
        $report = new Report;

        $step1 = new Step("Example step");
        $report->executeStep($step1, function ($step) {
            // nothing
        });

        $report->check("Step 2", function ($step) {
            // nothing
        });

        $this->assertTrue($step1->isSuccessful());
        $this->assertTrue($report->isSuccessful());
        $this->assertFalse($report->isFailed());
        $this->assertCount(2, $report->getSteps());
    }

    public function testFailedReport()
    {
        $report = new Report;

        try {
            $report->check("Step 1", function ($step) {
                throw new AssertionFailedError("Test error");
            });
        } catch (BaseTestError $e) {
            // nothing
        }

        $this->assertTrue($report->isFailed());
        $this->assertFalse($report->isSuccessful());

        $lastError = $report->getLastError();
        $this->assertInstanceOf(
            'TaskChecker\Errors\AssertionFailedError', 
            $lastError
        );
    }

    public function testReportStepNesting()
    {
        $report = new Report;
        $report->check("Parent", function ($parent) use ($report) {
            $report->check("Child", function ($child) {
                // Nothing
            });
        });

        $this->assertCount(1, $report->getSteps());
        $steps = $report->getSteps();
        $parent = $steps[0];
        $this->assertCount(1, $parent->getChildren());
    }
}

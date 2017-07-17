<?php 

namespace Tests\TaskChecker;

use TaskChecker\Reporter\ConsolePrinter;
use TaskChecker\Task;
use Tests\TaskChecker\Helper\TestHelper;

/**
 * Test all scenarios against solution examples
 */
class ScenarionsIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testExampleSolutions()
    {
        $taskListMgr = TestHelper::getService('task_list_manager');
        $tasks = $taskListMgr->getTaskList();

        foreach ($tasks as $task) {

            $taskDir = $taskListMgr->getTaskScenarioDirectory($task);
            assert(is_dir($taskDir));

            $passExamples = glob("$taskDir/pass/*");
            $failExamples = glob("$taskDir/fail/*");

            printf(
                "Check task %s, %d pass examples, %s fail examples\n", 
                $task->getId(),
                count($passExamples),
                count($failExamples)
            );

            foreach ($passExamples as $passExampleFile) {
                $this->checkSolution($task, $passExampleFile, true);
            }

            foreach ($failExamples as $failExampleFile) {
                $this->checkSolution($task, $failExampleFile, false);
            }
        }
    }

    private function checkSolution(Task $task, $solutionFile, $expectedResult)
    {
        $taskListMgr = TestHelper::getService('task_list_manager');
        $moduleFactory = TestHelper::getService('module_factory');

        $tester = $taskListMgr->createTesterForTask($moduleFactory, $task);
        $code = file_get_contents($solutionFile);

        $report = $tester->run($code);

        if ($expectedResult !== $report->isSuccessful()) {

            echo "Report from test {$task->getId()}:\n";

            $cp = new ConsolePrinter;
            $cp->printReport($report);
        }

        $this->assertEquals(
            $expectedResult,
            $report->isSuccessful(),
            sprintf(
                "expected solution '%s' for test '%s' to end with %s",
                $solutionFile,
                $task->getId(),
                $expectedResult ? 'success' : 'failure'
            )
        );
    }
}


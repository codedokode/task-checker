<?php 

namespace Tests\TaskChecker;

use TaskChecker\Problem;
use TaskChecker\Reporter\ConsolePrinter;
use Tests\TaskChecker\Helper\TestHelper;

/**
 * Test all scenarios against solution examples
 */
class ScenariosIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testExampleSolutions()
    {
        $problemService = TestHelper::getService('problem_service');
        $problems = $problemService->getProblemList();

        foreach ($problems as $problem) {

            $scenarioDir = $problemService->getScenariosDirectory($problem);
            assert(is_dir($scenarioDir));

            $passExamples = glob("$scenarioDir/pass/*");
            $failExamples = glob("$scenarioDir/fail/*");

            printf(
                "Check problem %s, %d pass examples, %s fail examples\n", 
                $problem->getId(),
                count($passExamples),
                count($failExamples)
            );

            foreach ($passExamples as $passExampleFile) {
                $this->checkSolution($problem, $passExampleFile, true);
            }

            foreach ($failExamples as $failExampleFile) {
                $this->checkSolution($problem, $failExampleFile, false);
            }
        }
    }

    private function checkSolution(Problem $problem, $solutionFile, $expectedResult)
    {
        $problemService = TestHelper::getService('problem_service');
        $moduleFactory = TestHelper::getService('module_factory');

        $tester = $problemService->createTesterForProblem($moduleFactory, $problem);
        $code = file_get_contents($solutionFile);

        $report = $tester->run($code);

        if ($expectedResult !== $report->isSuccessful()) {

            echo "Report from test {$problem->getId()}:\n";

            $cp = new ConsolePrinter;
            $cp->printReport($report);
        }

        $this->assertEquals(
            $expectedResult,
            $report->isSuccessful(),
            sprintf(
                "expected solution '%s' for test '%s' to end with %s",
                $solutionFile,
                $problem->getId(),
                $expectedResult ? 'success' : 'failure'
            )
        );
    }
}


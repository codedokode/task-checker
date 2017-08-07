<?php 

namespace TaskChecker;

use TaskChecker\Problem;
use TaskChecker\ProblemParser\ProblemSerializer;
use TaskChecker\Test\ScenarioTest;

class ProblemService
{
    private $problemList;
    private $problemsWithoutTesters;
    private $problemBaseDir;

    public function __construct()
    {
        $this->problemBaseDir = dirname(__DIR__) . '/scenarios';
        list($this->problemList, $this->problemsWithoutTesters) = 
            $this->buildProblemList(); 
    }

    private function buildProblemList()
    {
        $problemsWithTester = [];
        $unimplementedProblems = [];

        $path = $this->getProblemListLocation();

        if (!file_exists($path)) {
            return [ [], [] ];
        }

        $serializer = new ProblemSerializer;
        $jsonData = file_get_contents($path);
        $problems = $serializer->deserialize($jsonData);

        foreach ($problems as $problem) {
            $dir = $this->getScenariosDirectory($problem);
            if (is_dir($dir)) {
                $problemsWithTester[] = $problem;
            } else {
                $unimplementedProblems[] = $problem;
            }
        }

        // $problems[] = new Problem(
        //     'hello-world', 
        //     'Первая программа',
        //     'Напишите программу, которая выводит какой-нибудь текст, например «Hello, World!»'
        // );

        return [$problemsWithTester, $unimplementedProblems];
    }
    
    public function getProblemList()
    {
        return $this->problemList;
    }

    public function getProblemWithoutTesterList()
    {
        return $this->problemsWithoutTesters;
    }

    public function getProblemById($id)
    {
        foreach ($this->getProblemList() as $problem) {
            if ($problem->getId() == $id) {
                return $problem;
            }
        }

        foreach ($this->getProblemWithoutTesterList() as $problem) {
            if ($problem->getId() == $id) {
                return $problem;
            }
        }

        return null;
    }

    private function getScriptNameForProblem(Problem $problem)
    {
        return $this->getScenariosDirectory($problem) . '/tester.php'; 
    }

    public function getScenariosDirectory(Problem $problem)
    {
        return $this->problemBaseDir . '/' . $problem->getId();
    }
    
    public function createTesterForProblem(ModuleFactory $factory, Problem $problem)
    {
        $scriptName = $this->getScriptNameForProblem($problem);
        return new ScenarioTest($scriptName, $factory);
    }

    public function getProblemListLocation()
    {
        return $this->problemBaseDir . '/problems.json';
    }
}


<?php 

namespace TaskChecker;

use TaskChecker\Test\ScenarioTest;

class TaskListManager
{
    private $taskList;
    private $tasksBaseDir;

    public function __construct()
    {
        $this->taskList = $this->buildTaskList();
        $this->tasksBaseDir = dirname(__DIR__) . '/scenarios';
    }

    private function buildTaskList()
    {
        $tasks = [];
        $tasks[] = new Task(
            'hello-world', 
            'Первая программа',
            'Напишите программу, которая выводит какой-нибудь текст, например «Hello, World!»'
        );

        // $tasks[] = new Task(
        //     'random-number', 
        //     'Случайное число',
        //     'Напишите программу, которая имитирует бросок кубика и выводит случайное число от 1 до 6'
        // );

        return $tasks;
    }
    
    public function getTaskList()
    {
        return $this->taskList;
    }

    public function getTaskById($id)
    {
        foreach ($this->getTaskList() as $task) {
            if ($task->getId() == $id) {
                return $task;
            }
        }

        return null;
    }

    private function getScriptNameForTask(Task $task)
    {
        return $this->getTaskScenarioDirectory($task) . '/tester.php'; 
    }

    public function getTaskScenarioDirectory(Task $task)
    {
        return $this->tasksBaseDir . '/' . $task->getId();
    }
    
    public function createTesterForTask(ModuleFactory $factory, Task $task)
    {
        $scriptName = $this->getScriptNameForTask($task);
        return new ScenarioTest($scriptName, $factory);
    }
}


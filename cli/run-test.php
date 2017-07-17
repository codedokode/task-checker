<?php

use TaskChecker\Codebot\EvalClient;
use TaskChecker\Errors\BaseTestError;
use TaskChecker\ModuleFactory;
use TaskChecker\Reporter\ConsolePrinter;
use TaskChecker\Reporter\Report;
use TaskChecker\Test\ScenarioTest;

require_once __DIR__ . '/../src/bootstrap.php';

$codebot = new EvalClient();
$moduleFactory = new ModuleFactory($codebot);

$testFile = $argv[1];
$codeFile = $argv[2];

if (!file_exists($testFile)) {
    throw new \Exception("File not exists: $testFile");
}

if (!file_exists($codeFile)) {
    throw new \Exception("File not exists: $codeFile");
}

$code = file_get_contents($codeFile);
$test = new ScenarioTest($testFile, $moduleFactory);

$report = $test->run($code);

if ($report->isFailed()) {
    printf("Failed: %s\n\n", $report->getLastError()->getErrorText());
} else {
    printf("Success\n\n");
}

$consolePrinter = new ConsolePrinter;
$consolePrinter->printReport($report);


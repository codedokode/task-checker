<?php

use Codebot\EvalClient;
use Errors\Error;
use Reporter\ConsolePrinter;
use Reporter\Reporter;
use Test\ScenarioTest;

require_once __DIR__ . '/_bootstrap.php';

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
$reporter = new Reporter;

$test = new ScenarioTest($testFile, $moduleFactory);

try {
    $test->run($reporter, $code);
} catch (Error $e) {
    echo "Error: {$e->getErrorText()}\n\n";
}

$consolePrinter = new ConsolePrinter;
$consolePrinter->printReport($reporter);

